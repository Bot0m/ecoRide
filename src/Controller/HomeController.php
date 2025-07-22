<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RideRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur principal pour la page d'accueil
 */
final class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(RideRepository $rideRepository, ReviewRepository $reviewRepository): Response
    {
        $todayRides = [];
        $userParticipations = [];
        
        if ($this->getUser()) {
            /** @var User $user */
            $user = $this->getUser();
            
            // Récupérer les trajets du jour pour l'utilisateur connecté
            $todayRides = $rideRepository->findTodayRidesForUser($user);
            
            // Récupérer les participations de l'utilisateur pour afficher le bon état des boutons
            foreach ($user->getParticipations() as $participation) {
                $userParticipations[$participation->getRide()->getId()] = $participation->getStatus();
            }
        }

        // Récupérer les 3 derniers avis validés avec 3 étoiles ou plus
        $topReviews = $reviewRepository->createQueryBuilder('r')
            ->leftJoin('r.author', 'author')
            ->leftJoin('r.reviewedUser', 'reviewed')
            ->leftJoin('r.participation', 'p')
            ->leftJoin('p.ride', 'ride')
            ->addSelect('author', 'reviewed', 'p', 'ride')
            ->where('r.isValidated = :validated')
            ->andWhere('r.rating >= :minRating')
            ->setParameter('validated', true)
            ->setParameter('minRating', 3)
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'todayRides' => $todayRides,
            'userParticipations' => $userParticipations,
            'topReviews' => $topReviews,
        ]);
    }
} 