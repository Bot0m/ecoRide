<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeCrontrollerController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(RideRepository $rideRepository): Response
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

        return $this->render('home/index.html.twig', [
            'todayRides' => $todayRides,
            'userParticipations' => $userParticipations,
        ]);
    }
}
