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
