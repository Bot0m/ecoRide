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

        // Récupérer tous les voyages avec pagination
        $rides = $rideRepository->createQueryBuilder('r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.vehicle', 'v')
            ->addSelect('d', 'v')
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.departureTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        // Compter le total pour la pagination
        $totalRides = $rideRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalRides / $limit);

        return $this->render('employe/all_rides.html.twig', [
            'rides' => $rides,
            'currentPage' => $page,
            'totalPages' => $totalPages,
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
            ->addSelect('author', 'reviewed')
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
