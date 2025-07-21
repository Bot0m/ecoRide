<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Entity\Ride;
use App\Entity\User;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RideController extends AbstractController
{
    #[Route('/covoiturages', name: 'app_rides')]
    public function index(Request $request, RideRepository $rideRepository): Response
    {
        $departure = $request->query->get('departure');
        $arrival = $request->query->get('arrival');
        $date = $request->query->get('date');

        $ridesMatchingSearch = [];
        $searchResult = [
            'rides' => [],
            'type' => null,
            'isAlternative' => false,
            'searchedDate' => null
        ];
        if ($departure && $arrival && $date) {
            try {
                $dateObj = new \DateTimeImmutable($date);
                $searchResult = $rideRepository->findMatchingRidesWithFallback($departure, $arrival, $dateObj);
                $ridesMatchingSearch = $searchResult['rides'];
            } catch (\Exception $e) {
                // gérer erreur de date invalide si besoin
            }
        }

        $ridesUpcoming = $rideRepository->findUpcomingRides();

        // Récupérer les participations de l'utilisateur connecté pour afficher le bon état des boutons
        $userParticipations = [];
        $userParticipationsDetails = [];
        if ($this->getUser()) {
            /** @var User $user */
            $user = $this->getUser();
            foreach ($user->getParticipations() as $participation) {
                $userParticipations[$participation->getRide()->getId()] = $participation->getStatus();
                $userParticipationsDetails[$participation->getRide()->getId()] = [
                    'status' => $participation->getStatus(),
                    'seatsCount' => $participation->getSeatsCount()
                ];
            }
        }

        return $this->render('ride/index.html.twig', [
            'ridesMatchingSearch' => $ridesMatchingSearch,
            'ridesUpcoming' => $ridesUpcoming,
            'search' => compact('departure', 'arrival', 'date'),
            'searchResult' => $searchResult,
            'userParticipations' => $userParticipations,
            'userParticipationsDetails' => $userParticipationsDetails,
        ]);
    }

    #[Route('/trajets/{id}/reserver', name: 'app_ride_reserve', methods: ['POST'])]
    public function reserveRide(Ride $ride, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier que l'utilisateur est connecté
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour réserver un trajet'], 401);
        }

        // Vérifier que le voyage n'est pas annulé
        if ($ride->getStatus() !== 'actif') {
            return new JsonResponse(['error' => 'Ce voyage n\'est plus disponible'], 400);
        }

        // Récupérer le nombre de places demandées
        $data = json_decode($request->getContent(), true);
        $seatsRequested = $data['seats'] ?? 1;
        
        // Valider le nombre de places
        if ($seatsRequested < 1) {
            return new JsonResponse(['error' => 'Le nombre de places doit être au moins de 1'], 400);
        }
        
        if ($seatsRequested > 4) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas réserver plus de 4 places'], 400);
        }

        // Vérifier que l'utilisateur n'est pas le conducteur
        if ($ride->getDriver() === $user) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas réserver votre propre trajet'], 400);
        }

        // Vérifier qu'il y a suffisamment de places disponibles
        if ($ride->getAvailableSeats() < $seatsRequested) {
            return new JsonResponse(['error' => "Il n'y a que {$ride->getAvailableSeats()} place(s) disponible(s) pour ce trajet"], 400);
        }

        // Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
        foreach ($ride->getParticipations() as $participation) {
            if ($participation->getUser() === $user) {
                return new JsonResponse(['error' => 'Vous avez déjà réservé ce trajet'], 400);
            }
        }

        // Calculer le coût total (prix par place × nombre de places + commission fixe de 2 crédits)
        $totalPrice = $ride->getPrice() * $seatsRequested;
        $requiredCredits = $totalPrice + 2; // Prix total + commission fixe de 2 crédits
        
        if ($user->getCredits() < $requiredCredits) {
            return new JsonResponse(['error' => "Vous n'avez pas suffisamment de crédits. Il vous faut {$requiredCredits} crédits ({$totalPrice} pour {$seatsRequested} place(s) + 2 crédits de commission)"], 400);
        }

        try {
            // Créer la participation
            $participation = new Participation();
            $participation->setUser($user);
            $participation->setRide($ride);
            $participation->setStatus('en_attente'); // En attente de validation du conducteur
            $participation->setHasGivenReview(false);
            $participation->setTripValidated(false);
            $participation->setSeatsCount($seatsRequested); // Définir le nombre de places réservées

            // Débiter les crédits de l'utilisateur
            $user->setCredits($user->getCredits() - $requiredCredits);

            // Créditer le conducteur (prix total du trajet seulement, pas la commission)
            $driver = $ride->getDriver();
            $driver->setCredits($driver->getCredits() + $totalPrice);

            // Réduire le nombre de places disponibles
            $ride->setAvailableSeats($ride->getAvailableSeats() - $seatsRequested);

            // Persister les changements
            $entityManager->persist($participation);
            $entityManager->flush();

            $seatsText = $seatsRequested > 1 ? "{$seatsRequested} places" : "1 place";
            return new JsonResponse([
                'success' => true,
                'message' => "Réservation de {$seatsText} effectuée ! Votre demande est en attente de validation par le conducteur.",
                'newCredits' => $user->getCredits()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la réservation : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/ride/{id}/details', name: 'app_ride_details', methods: ['GET'])]
    public function getRideDetails(Ride $ride): JsonResponse
    {

        $driver = $ride->getDriver();
        $vehicle = $ride->getVehicle();
        
        // Récupérer les participants acceptés ET annulés (pour l'historique)
        $participants = [];
        foreach ($ride->getParticipations() as $participation) {
            if ($participation->getStatus() === 'acceptee' || $participation->getStatus() === 'annulee') {
                $participants[] = [
                    'id' => $participation->getUser()->getId(),
                    'pseudo' => $participation->getUser()->getPseudo(),
                    'avatar' => $participation->getUser()->getAvatar(),
                    'userType' => $participation->getUser()->getUserType(),
                    'status' => $participation->getStatus(), // Ajouter le statut pour différencier
                    'seatsCount' => $participation->getSeatsCount(), // Ajouter le nombre de places
                ];
            }
        }

        // Calculer la note moyenne du conducteur (par défaut 5/5)
        $driverRating = 5.0;
        $totalReviews = count($driver->getReviewsReceived());
        if ($totalReviews > 0) {
            $totalRating = 0;
            foreach ($driver->getReviewsReceived() as $review) {
                $totalRating += $review->getRating();
            }
            $driverRating = round($totalRating / $totalReviews, 1);
        }

        $rideDetails = [
            'id' => $ride->getId(),
            'departure' => $ride->getDeparture(),
            'arrival' => $ride->getArrival(),
            'date' => $ride->getDate()->format('d/m/Y'),
            'departureTime' => $ride->getDepartureTime()->format('H:i'),
            'arrivalTime' => $ride->getArrivalTime()->format('H:i'),
            'price' => $ride->getPrice(),
            'availableSeats' => $ride->getAvailableSeats(),
            'isEcological' => $ride->isEcological(),
            'driver' => [
                'id' => $driver->getId(),
                'pseudo' => $driver->getPseudo(),
                'avatar' => $driver->getAvatar(),
                'bio' => $driver->getBio(),
                'userType' => $driver->getUserType(),
                'rating' => $driverRating,
                'totalReviews' => $totalReviews,
            ],
            'vehicle' => [
                'brand' => $vehicle->getBrand(),
                'model' => $vehicle->getModel(),
                'color' => $vehicle->getColor(),
                'energy' => $vehicle->getEnergy(),
                'seats' => $vehicle->getSeats(),
            ],
            'participants' => $participants,
            'participantsCount' => count($participants),
        ];

        return new JsonResponse($rideDetails);
    }

    #[Route('/ride/{id}/start', name: 'app_ride_start', methods: ['POST'])]
    public function startRide(Ride $ride, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur du trajet
        if ($ride->getDriver() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à démarrer ce trajet'], 403);
        }
        
        // Vérifier que le trajet est en statut actif
        if ($ride->getStatus() !== 'actif') {
            return new JsonResponse(['success' => false, 'message' => 'Ce trajet ne peut pas être démarré'], 400);
        }
        
        // Vérifier que le trajet est aujourd'hui
        $today = new \DateTimeImmutable('today');
        if ($ride->getDate()->format('Y-m-d') !== $today->format('Y-m-d')) {
            return new JsonResponse(['success' => false, 'message' => 'Ce trajet ne peut être démarré que le jour prévu'], 400);
        }
        
        // Démarrer le trajet
        $ride->setStatus('en_cours');
        $ride->setStartedAt(new \DateTime());
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Trajet démarré avec succès']);
    }

    #[Route('/ride/{id}/complete', name: 'app_ride_complete', methods: ['POST'])]
    public function completeRide(Ride $ride, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur du trajet
        if ($ride->getDriver() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à terminer ce trajet'], 403);
        }
        
        // Vérifier que le trajet est en cours
        if ($ride->getStatus() !== 'en_cours') {
            return new JsonResponse(['success' => false, 'message' => 'Ce trajet ne peut pas être terminé'], 400);
        }
        
        // Terminer le trajet
        $ride->setStatus('termine');
        $ride->setCompletedAt(new \DateTime());
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Trajet terminé avec succès']);
    }
}