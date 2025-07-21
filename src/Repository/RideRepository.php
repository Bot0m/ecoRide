<?php

namespace App\Repository;

use App\Entity\Ride;
use App\Entity\User;
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
            ->andWhere('r.status = :status')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('arrival', '%' . $arrival . '%')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('status', 'actif')
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMatchingRidesWithFallback(string $departure, string $arrival, \DateTimeInterface $date): array
    {
        // Recherche exacte
        $exactMatches = $this->createQueryBuilder('r')
            ->where('r.departure LIKE :departure')
            ->andWhere('r.arrival LIKE :arrival')
            ->andWhere('r.date = :date')
            ->andWhere('r.status = :status')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('arrival', '%' . $arrival . '%')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('status', 'actif')
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departure', 'ASC')
            ->getQuery()
            ->getResult();

        if (!empty($exactMatches)) {
            return [
                'rides' => $exactMatches, 
                'type' => 'exact',
                'isAlternative' => false,
                'searchedDate' => $date
            ];
        }

        // Recherche par date seulement
        $dateMatches = $this->createQueryBuilder('r')
            ->where('r.date = :date')
            ->andWhere('r.status = :status')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('status', 'actif')
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.departure', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'rides' => $dateMatches, 
            'type' => 'date_only',
            'isAlternative' => true,
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
            ->andWhere('r.status = :status')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('currentTime', $currentTime)
            ->setParameter('status', 'actif')
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
        
        // Compter les voyages passés où l'utilisateur est conducteur (seulement les voyages actifs)
        $drivenCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.driver = :user')
            ->andWhere('r.date < :today')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('status', 'actif')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les voyages passés où l'utilisateur est passager (seulement les voyages actifs)
        $passengerCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->andWhere('r.date < :today')
            ->andWhere('r.status = :rideStatus')
            ->setParameter('user', $user)
            ->setParameter('status', 'acceptee')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('rideStatus', 'actif')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$drivenCount + (int)$passengerCount;
    }

    public function countUpcomingRidesByUser($user): int
    {
        $today = new \DateTimeImmutable('today');
        
        // Compter les voyages futurs où l'utilisateur est conducteur (seulement les voyages actifs)
        $drivenCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.driver = :user')
            ->andWhere('r.date >= :today')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('status', 'actif')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les voyages futurs où l'utilisateur est passager (seulement les voyages actifs)
        $passengerCount = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->andWhere('r.date >= :today')
            ->andWhere('r.status = :rideStatus')
            ->setParameter('user', $user)
            ->setParameter('status', 'acceptee')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('rideStatus', 'actif')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$drivenCount + (int)$passengerCount;
    }

    public function findTodayRidesForUser(User $user): array
    {
        $today = new \DateTimeImmutable('today');
        
        // Récupérer les trajets où l'utilisateur est conducteur
        $drivenRides = $this->createQueryBuilder('r')
            ->where('r.driver = :user')
            ->andWhere('r.date = :today')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('statuses', ['actif', 'en_cours', 'termine'])
            ->orderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Récupérer les trajets où l'utilisateur est passager (via participations acceptées)
        $passengerRides = $this->createQueryBuilder('r')
            ->join('r.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :participationStatus')
            ->andWhere('r.date = :today')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('participationStatus', 'acceptee')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('statuses', ['actif', 'en_cours', 'termine'])
            ->orderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Fusionner et trier tous les trajets
        $allRides = array_merge($drivenRides, $passengerRides);
        
        // Supprimer les doublons et trier par heure de départ
        $uniqueRides = [];
        foreach ($allRides as $ride) {
            $uniqueRides[$ride->getId()] = $ride;
        }
        
        // Trier par heure de départ
        usort($uniqueRides, function($a, $b) {
            return $a->getDepartureTime() <=> $b->getDepartureTime();
        });
        
        return $uniqueRides;
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
