<?php

namespace App\Repository;

use App\Entity\Ride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ride>
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    public function findMatchingRides(string $departure, string $arrival, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.departure LIKE :departure')
            ->andWhere('r.arrival LIKE :arrival')
            ->andWhere('r.date = :date')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('arrival', '%' . $arrival . '%')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMatchingRidesWithFallback(string $departure, string $arrival, \DateTimeInterface $date): array
    {
        // Rechercher d'abord pour la date exacte
        $exactResults = $this->findMatchingRides($departure, $arrival, $date);
        
        if (!empty($exactResults)) {
            return [
                'rides' => $exactResults,
                'isAlternative' => false,
                'searchedDate' => $date
            ];
        }
        
        // Si aucun résultat pour la date exacte, chercher les 7 jours suivants
        $startDate = (new \DateTimeImmutable($date->format('Y-m-d')))->modify('+1 day');
        $endDate = (new \DateTimeImmutable($date->format('Y-m-d')))->modify('+7 days');
        
        $alternativeResults = $this->createQueryBuilder('r')
            ->where('r.departure LIKE :departure')
            ->andWhere('r.arrival LIKE :arrival')
            ->andWhere('r.date >= :startDate AND r.date <= :endDate')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('arrival', '%' . $arrival . '%')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();
        
        return [
            'rides' => $alternativeResults,
            'isAlternative' => !empty($alternativeResults),
            'searchedDate' => $date
        ];
    }

    public function findUpcomingRides(): array
    {
        $now = new \DateTimeImmutable();
        $today = new \DateTimeImmutable('today');
        $currentTime = $now->format('H:i:s');
        
        return $this->createQueryBuilder('r')
            ->where('r.date > :today OR (r.date = :today AND r.departureTime > :currentTime)')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('currentTime', $currentTime)
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentRidesByUser($user, int $limit = 5): array
    {
        // Récupérer les voyages où l'utilisateur est conducteur
        $drivenRides = $this->createQueryBuilder('r')
            ->where('r.driver = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.departureTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Récupérer les voyages où l'utilisateur est passager (via participations acceptées)
        $passengerRides = $this->createQueryBuilder('r')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'acceptee')
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.departureTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Fusionner et trier tous les voyages
        $allRides = array_merge($drivenRides, $passengerRides);
        
        // Supprimer les doublons et trier par date/heure décroissante
        $uniqueRides = [];
        foreach ($allRides as $ride) {
            $uniqueRides[$ride->getId()] = $ride;
        }
        
        // Trier par date et heure décroissante
        usort($uniqueRides, function($a, $b) {
            $dateTimeA = clone $a->getDate();
            $dateTimeA->setTime(
                $a->getDepartureTime()->format('H'),
                $a->getDepartureTime()->format('i')
            );
            
            $dateTimeB = clone $b->getDate();
            $dateTimeB->setTime(
                $b->getDepartureTime()->format('H'),
                $b->getDepartureTime()->format('i')
            );
            
            return $dateTimeB <=> $dateTimeA; // Ordre décroissant
        });

        // Retourner seulement les $limit premiers
        return array_slice($uniqueRides, 0, $limit);
    }

    public function countCompletedRidesByUser($user): int
    {
        $today = new \DateTimeImmutable('today');
        
        // Compter les voyages passés où l'utilisateur est conducteur
        $drivenCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.driver = :user')
            ->andWhere('r.date < :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les voyages passés où l'utilisateur est passager
        $passengerCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->andWhere('r.date < :today')
            ->setParameter('user', $user)
            ->setParameter('status', 'acceptee')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$drivenCount + (int)$passengerCount;
    }

    public function countUpcomingRidesByUser($user): int
    {
        $today = new \DateTimeImmutable('today');
        
        // Compter les voyages futurs où l'utilisateur est conducteur
        $drivenCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.driver = :user')
            ->andWhere('r.date >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les voyages futurs où l'utilisateur est passager
        $passengerCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->andWhere('r.date >= :today')
            ->setParameter('user', $user)
            ->setParameter('status', 'acceptee')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$drivenCount + (int)$passengerCount;
    }
    //    /**
    //     * @return Ride[] Returns an array of Ride objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Ride
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
