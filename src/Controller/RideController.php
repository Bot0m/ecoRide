<?php

namespace App\Controller;

use App\Repository\RideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        if ($departure && $arrival && $date) {
            try {
                $dateObj = new \DateTimeImmutable($date);
                $ridesMatchingSearch = $rideRepository->findMatchingRides($departure, $arrival, $dateObj);
            } catch (\Exception $e) {
                // gérer erreur de date invalide si besoin
            }
        }

        $ridesUpcoming = $rideRepository->findUpcomingRides();

        return $this->render('ride/index.html.twig', [
            'ridesMatchingSearch' => $ridesMatchingSearch,
            'ridesUpcoming' => $ridesUpcoming,
            'search' => compact('departure', 'arrival', 'date'),
        ]);
    }
}