<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ride;
use App\Entity\Review;
use App\Repository\RideRepository;
use App\Repository\ReviewRepository;

final class EmployeController extends AbstractController
{
    #[Route('/employe', name: 'employe_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');
        return $this->render('employe/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/employe/tous-les-voyages', name: 'employe_all_rides')]
    public function allRides(Request $request, RideRepository $rideRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        // Récupérer les voyages à venir (date >= aujourd'hui)
        $upcomingRides = $rideRepository->createQueryBuilder('r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.vehicle', 'v')
            ->addSelect('d', 'v')
            ->where('r.date >= :today')
            ->setParameter('today', $today)
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departureTime', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        // Compter le total des voyages à venir pour la pagination
        $totalUpcomingRides = $rideRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $totalUpcomingPages = ceil($totalUpcomingRides / $limit);

        // Récupérer les voyages passés (date < aujourd'hui)
        $pastRides = $rideRepository->createQueryBuilder('r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.vehicle', 'v')
            ->addSelect('d', 'v')
            ->where('r.date < :today')
            ->setParameter('today', $today)
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.departureTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        // Compter le total des voyages passés pour la pagination
        $totalPastRides = $rideRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date < :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $totalPastPages = ceil($totalPastRides / $limit);

        // Total général
        $totalRides = $totalUpcomingRides + $totalPastRides;

        return $this->render('employe/all_rides.html.twig', [
            'upcomingRides' => $upcomingRides,
            'pastRides' => $pastRides,
            'currentPage' => $page,
            'totalUpcomingPages' => $totalUpcomingPages,
            'totalPastPages' => $totalPastPages,
            'totalUpcomingRides' => $totalUpcomingRides,
            'totalPastRides' => $totalPastRides,
            'totalRides' => $totalRides,
        ]);
    }

    #[Route('/employe/les-avis', name: 'employe_all_reviews')]
    public function allReviews(Request $request, ReviewRepository $reviewRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer tous les avis avec pagination
        $reviews = $reviewRepository->createQueryBuilder('r')
            ->leftJoin('r.author', 'author')
            ->leftJoin('r.reviewedUser', 'reviewed')
            ->leftJoin('r.participation', 'p')
            ->leftJoin('p.ride', 'ride')
            ->addSelect('author', 'reviewed', 'p', 'ride')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        // Compter le total pour la pagination
        $totalReviews = $reviewRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalReviews / $limit);

        return $this->render('employe/all_reviews.html.twig', [
            'reviews' => $reviews,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalReviews' => $totalReviews,
        ]);
    }
}
