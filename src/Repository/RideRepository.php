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

    public function findUpcomingRides(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.date > :now')
            ->setParameter('now', new \DateTimeImmutable('today'))
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
