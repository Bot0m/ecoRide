<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Preference;
use App\Entity\Ride;
use App\Entity\Participation;
use App\Form\RideType;
use App\Repository\RideRepository;
use App\Repository\ParticipationRepository;

class UserController extends AbstractController
{
    #[Route('/credit', name: 'credit')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('user/credit.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/credit/add', name: 'credit_add', methods: ['POST'])]
    public function addCredits(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $creditsToAdd = $data['credits'] ?? 0;

        // Validation des montants autorisés
        $allowedAmounts = [10, 15, 20, 30];
        if (!in_array($creditsToAdd, $allowedAmounts)) {
            return new JsonResponse(['error' => 'Montant de crédits non autorisé'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        $currentCredits = $user->getCredits();
        $newCredits = $currentCredits + $creditsToAdd;

        $user->setCredits($newCredits);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'newCredits' => $newCredits,
            'addedCredits' => $creditsToAdd,
            'message' => "Félicitations ! Vous avez reçu {$creditsToAdd} crédits gratuits."
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/update', name: 'profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Mise à jour des informations
        if (isset($data['pseudo'])) {
            $user->setPseudo($data['pseudo']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['userType'])) {
            $user->setUserType($data['userType']);
        }
        
        if (isset($data['bio'])) {
            $user->setBio($data['bio']);
        }
        
        if (isset($data['avatar'])) {
            $user->setAvatar($data['avatar']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Profil mis à jour avec succès !'
        ]);
    }

    #[Route('/profile/upload-avatar', name: 'profile_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_USER');

            $avatarFile = $request->files->get('avatar');
            
            if (!$avatarFile) {
                return new JsonResponse(['error' => 'Aucun fichier sélectionné'], 400);
            }
            
            if (!$avatarFile->isValid()) {
                return new JsonResponse(['error' => 'Fichier invalide: ' . $avatarFile->getErrorMessage()], 400);
            }

            // Vérifier le type de fichier
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'];
            
            $mimeType = $avatarFile->getMimeType();
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Format de fichier non autorisé: ' . $mimeType], 400);
            }

            // Vérifier la taille (20 Mo max)
            if ($avatarFile->getSize() > 20 * 1024 * 1024) {
                return new JsonResponse(['error' => 'Fichier trop volumineux (max 20 Mo)'], 400);
            }

            /** @var User $user */
            $user = $this->getUser();
            
            // Créer un nom de fichier unique avec extension basée sur le MIME type
            $extension = match($mimeType) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/heic' => 'heic',
                'image/heif' => 'heif',
                default => 'jpg'
            };
            
            $fileName = 'avatar_' . $user->getId() . '_' . uniqid() . '.' . $extension;
            
            // Définir le répertoire de destination
            $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            
            // Créer le répertoire s'il n'existe pas
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            // Déplacer le fichier
            $avatarFile->move($uploadDirectory, $fileName);
            
            // Supprimer l'ancien avatar s'il existe
            if ($user->getAvatar() && strpos($user->getAvatar(), '/uploads/avatars/') !== false) {
                $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getAvatar();
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
            
            // Mettre à jour l'avatar dans la base de données
            $user->setAvatar('/uploads/avatars/' . $fileName);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'avatarUrl' => '/uploads/avatars/' . $fileName
            ]);

        } catch (FileException $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur générale: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/profile/preferences/update', name: 'profile_preferences_update', methods: ['POST'])]
    public function updatePreferences(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer ou créer les préférences
        $preference = $user->getPreference();
        if (!$preference) {
            $preference = new Preference();
            $user->setPreference($preference);
        }
        
        // Mise à jour des préférences
        if (isset($data['smoker'])) {
            $preference->setSmoker((bool)$data['smoker']);
        }
        
        if (isset($data['animals'])) {
            $preference->setAnimals((bool)$data['animals']);
        }
        
        if (isset($data['customPreferences'])) {
            $preference->setCustomPreferences($data['customPreferences']);
        }

        $entityManager->persist($preference);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès !'
        ]);
    }

    #[Route('/mes-voyages', name: 'app_rides_user', methods: ['GET', 'POST'])]
    public function userRides(Request $request, RideRepository $rideRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        
        // Création du formulaire d'ajout
        $ride = new Ride();
        $form = $this->createForm(RideType::class, $ride, ['user' => $user]);
        $form->handleRequest($request);

        // Traitement de l'ajout si le formulaire est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $ride->setDriver($user);
            
            // Déterminer automatiquement si le voyage est écologique basé sur le véhicule
            $vehicle = $ride->getVehicle();
            $isEcological = in_array($vehicle->getEnergy(), ['Électrique', 'Hybride']);
            $ride->setIsEcological($isEcological);
            
            $entityManager->persist($ride);
            $entityManager->flush();

            return $this->redirectToRoute('app_rides_user');
        }

        // Récupération des voyages où l'utilisateur est conducteur
        $drivenRides = $rideRepository->findBy(['driver' => $user]);
        
        // Récupération des voyages où l'utilisateur est passager
        $passengerRides = $user->getRidesAsPassenger()->toArray();
        
        // Fusion et tri de tous les voyages de l'utilisateur
        $allUserRides = array_merge($drivenRides, $passengerRides);
        
        // Tri par date
        usort($allUserRides, function($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });
        
        // Séparation entre voyages futurs et passés
        $today = new \DateTime('today');
        $upcomingRides = [];
        $pastRides = [];
        
        foreach ($allUserRides as $ride) {
            if ($ride->getDate() >= $today) {
                $upcomingRides[] = $ride;
            } else {
                $pastRides[] = $ride;
            }
        }

        return $this->render('user/rides.html.twig', [
            'upcomingRides' => $upcomingRides,
            'pastRides' => $pastRides,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-voyages/{id}/supprimer', name: 'app_rides_delete')]
    public function deleteRide(Ride $ride, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if ($ride->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($ride);
        $entityManager->flush();

        return $this->redirectToRoute('app_rides_user');
    }

    #[Route('/participations/{id}/accepter', name: 'app_participation_accept', methods: ['POST'])]
    public function acceptParticipation(Participation $participation, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur du trajet
        if ($participation->getRide()->getDriver() !== $user) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à modifier cette participation'], 403);
        }
        
        // Vérifier que la participation est en attente
        if ($participation->getStatus() !== 'en_attente') {
            return new JsonResponse(['error' => 'Cette demande a déjà été traitée'], 400);
        }
        
        try {
            // Changer le statut de la participation
            $participation->setStatus('acceptee');
            
            // Ajouter l'utilisateur comme passager du trajet
            $ride = $participation->getRide();
            $passenger = $participation->getUser();
            $ride->addPassenger($passenger);
            
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Demande acceptée avec succès ! Le passager a été ajouté à votre trajet.'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'acceptation : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/participations/{id}/refuser', name: 'app_participation_refuse', methods: ['POST'])]
    public function refuseParticipation(Participation $participation, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur du trajet
        if ($participation->getRide()->getDriver() !== $user) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à modifier cette participation'], 403);
        }
        
        // Vérifier que la participation est en attente
        if ($participation->getStatus() !== 'en_attente') {
            return new JsonResponse(['error' => 'Cette demande a déjà été traitée'], 400);
        }
        
        try {
            // Changer le statut de la participation
            $participation->setStatus('refusee');
            
            // Rembourser l'utilisateur (prix + commission)
            $ride = $participation->getRide();
            $passenger = $participation->getUser();
            $refundAmount = $ride->getPrice() + 2; // Prix + commission
            
            $passenger->setCredits($passenger->getCredits() + $refundAmount);
            
            // Débiter le conducteur du prix qu'il avait reçu
            $driver = $ride->getDriver();
            $driver->setCredits($driver->getCredits() - $ride->getPrice());
            
            // Remettre une place disponible
            $ride->setAvailableSeats($ride->getAvailableSeats() + 1);
            
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Demande refusée. Le passager a été remboursé de ses crédits.'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors du refus : ' . $e->getMessage()], 500);
        }
    }
}