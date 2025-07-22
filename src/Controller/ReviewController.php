<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Ride;
use App\Entity\User;
use App\Entity\Participation;
use App\Repository\ReviewRepository;
use App\Repository\RideRepository;
use App\Repository\ParticipationRepository;
use App\Service\ReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AbstractController
{
    #[Route('/ride/{id}/users-to-review', name: 'app_ride_users_to_review', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUsersToReview(Ride $ride, ParticipationRepository $participationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUser = $this->getUser();
        $users = [];

        // Si l'utilisateur est le conducteur
        if ($ride->getDriver() === $currentUser) {
            // Il peut évaluer tous les passagers acceptés
            $participations = $participationRepository->findBy([
                'ride' => $ride,
                'status' => 'acceptee'
            ]);

            foreach ($participations as $participation) {
                $user = $participation->getUser();
                
                // Vérifier si le conducteur n'a pas déjà donné un avis pour ce passager sur ce trajet
                $existingReview = $entityManager->getRepository(Review::class)->findOneBy([
                    'author' => $currentUser,
                    'reviewedUser' => $user
                ]);
                
                // Vérifier si l'avis existe déjà pour cette participation spécifique (même trajet)
                if ($existingReview && $existingReview->getParticipation() && $existingReview->getParticipation()->getRide()->getId() === $ride->getId()) {
                    // L'avis existe déjà pour cette participation sur ce trajet
                } else {
                    $users[] = [
                        'id' => $user->getId(),
                        'pseudo' => $user->getPseudo(),
                        'role' => 'Passager'
                    ];
                }
            }
        } else {
            // Si l'utilisateur est un passager, il peut évaluer le conducteur et les autres passagers
            $userParticipation = $participationRepository->findOneBy([
                'ride' => $ride,
                'user' => $currentUser,
                'status' => 'acceptee'
            ]);

            if ($userParticipation) {
                // Évaluer le conducteur
                $driver = $ride->getDriver();
                
                // Le conducteur n'a pas de participation, on vérifie juste s'il n'a pas déjà reçu un avis
                $existingDriverReview = $entityManager->getRepository(Review::class)->findOneBy([
                    'author' => $currentUser,
                    'reviewedUser' => $driver
                ]);
                
                // Vérifier si l'avis existe déjà pour cette participation spécifique (même trajet)
                if ($existingDriverReview && $existingDriverReview->getParticipation() && $existingDriverReview->getParticipation()->getRide()->getId() === $ride->getId()) {
                    // L'avis existe déjà pour cette participation sur ce trajet
                } else {
                    $users[] = [
                        'id' => $driver->getId(),
                        'pseudo' => $driver->getPseudo(),
                        'role' => 'Conducteur'
                    ];
                }

                // Évaluer les autres passagers
                $otherParticipations = $participationRepository->findBy([
                    'ride' => $ride,
                    'status' => 'acceptee'
                ]);

                foreach ($otherParticipations as $participation) {
                    $user = $participation->getUser();
                    if ($user !== $currentUser) {
                        // Vérifier si l'utilisateur n'a pas déjà reçu un avis de ce passager
                        $existingReview = $entityManager->getRepository(Review::class)->findOneBy([
                            'author' => $currentUser,
                            'reviewedUser' => $user
                        ]);
                        
                        // Vérifier si l'avis existe déjà pour cette participation spécifique (même trajet)
                        if ($existingReview && $existingReview->getParticipation() && $existingReview->getParticipation()->getRide()->getId() === $ride->getId()) {
                            // L'avis existe déjà pour cette participation sur ce trajet
                        } else {
                            $users[] = [
                                'id' => $user->getId(),
                                'pseudo' => $user->getPseudo(),
                                'role' => 'Passager'
                            ];
                        }
                    }
                }
            }
        }

        return $this->json(['users' => $users]);
    }

    #[Route('/review/submit', name: 'app_review_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitReview(Request $request, EntityManagerInterface $entityManager, RideRepository $rideRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $currentUser = $this->getUser();

        if (!$data || !isset($data['rideId']) || !isset($data['reviewedUserId']) || !isset($data['rating'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $ride = $rideRepository->find($data['rideId']);
        $reviewedUser = $entityManager->getRepository(User::class)->find($data['reviewedUserId']);

        if (!$ride || !$reviewedUser) {
            return $this->json(['success' => false, 'message' => 'Trajet ou utilisateur non trouvé'], 404);
        }

        // Vérifier que l'utilisateur peut laisser un avis pour ce trajet
        if (!$this->canUserReviewRide($currentUser, $ride, $entityManager)) {
            return $this->json(['success' => false, 'message' => 'Vous ne pouvez pas laisser d\'avis pour ce trajet'], 403);
        }

        // Chercher un avis existant pour cet utilisateur sur ce trajet spécifique
        $existingReview = $entityManager->getRepository(Review::class)->findOneBy([
            'author' => $currentUser,
            'reviewedUser' => $reviewedUser
        ]);

        // Vérifier si l'avis existe déjà pour ce trajet
        if ($existingReview && $existingReview->getParticipation() && $existingReview->getParticipation()->getRide()->getId() === $ride->getId()) {
            return $this->json(['success' => false, 'message' => 'Vous avez déjà laissé un avis pour cet utilisateur sur ce trajet'], 400);
        }

        // Créer l'avis
        $review = new Review();
        $review->setAuthor($currentUser);
        $review->setReviewedUser($reviewedUser);
        $review->setRating($data['rating']);
        $review->setComment($data['comment'] ?? null);
        $review->setIsValidated(false); // Par défaut, l'avis n'est pas validé

        // Associer l'avis à la participation de l'auteur
        $authorParticipation = $this->getUserParticipation($currentUser, $ride, $entityManager);
        if ($authorParticipation) {
            $authorParticipation->setReview($review);
            $authorParticipation->setHasGivenReview(true);
        }

        try {
            $entityManager->persist($review);
            $entityManager->flush();

            // Mettre à jour la note moyenne de l'utilisateur évalué
            $this->updateUserAverageRating($reviewedUser, $entityManager);

            return new JsonResponse(['success' => true, 'message' => 'Avis envoyé avec succès'], 200, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            // Log l'erreur pour le debug
            error_log('Erreur lors de la création de l\'avis: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'avis: ' . $e->getMessage()], 500, ['Content-Type' => 'application/json']);
        }
    }

    #[Route('/employe/review/{id}/validate', name: 'app_review_validate', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function validateReview(Review $review, ReviewService $reviewService): JsonResponse
    {
        $reviewService->validateReview($review);

        return $this->json(['success' => true, 'message' => 'Avis validé avec succès']);
    }

    #[Route('/employe/review/{id}/reject', name: 'app_review_reject', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function rejectReview(Review $review, ReviewService $reviewService): JsonResponse
    {
        $reviewService->rejectReview($review);

        return $this->json(['success' => true, 'message' => 'Avis refusé et déplacé dans l\'historique']);
    }

    private function canUserReviewRide(User $user, Ride $ride, EntityManagerInterface $entityManager): bool
    {
        // L'utilisateur peut laisser un avis si :
        // 1. Il est le conducteur et le trajet est terminé
        // 2. Il est un passager accepté et le trajet est terminé
        if ($ride->getStatus() !== 'termine') {
            return false;
        }

        if ($ride->getDriver() === $user) {
            return true;
        }

        $participation = $this->getUserParticipation($user, $ride, $entityManager);
        return $participation && $participation->getStatus() === 'acceptee';
    }

    private function getUserParticipation(User $user, Ride $ride, EntityManagerInterface $entityManager): ?Participation
    {
        // Chercher d'abord une participation existante
        $participation = $entityManager->getRepository(Participation::class)
            ->findOneBy(['user' => $user, 'ride' => $ride]);
        
        // Si c'est le conducteur et qu'il n'a pas de participation, on en crée une
        if (!$participation && $ride->getDriver() === $user) {
            $participation = new Participation();
            $participation->setUser($user);
            $participation->setRide($ride);
            $participation->setStatus('acceptee'); // Le conducteur est automatiquement accepté
            $participation->setSeatsCount(1);
            $participation->setHasGivenReview(false);
            $participation->setTripValidated(true);
            
            $entityManager->persist($participation);
            $entityManager->flush();
        }
        
        return $participation;
    }

    private function updateUserAverageRating(User $user, EntityManagerInterface $entityManager): void
    {
        // Calculer la moyenne des avis validés
        $reviews = $entityManager->getRepository(Review::class)->findBy([
            'reviewedUser' => $user,
            'isValidated' => true
        ]);

        if (empty($reviews)) {
            // Si aucun avis validé, garder la note par défaut (5.0)
            $user->setAverageRating(5.0);
        } else {
            $totalRating = 0;
            foreach ($reviews as $review) {
                $totalRating += $review->getRating();
            }
            $averageRating = $totalRating / count($reviews);
            $user->setAverageRating(round($averageRating, 1));
        }

        $entityManager->flush();
    }

} 