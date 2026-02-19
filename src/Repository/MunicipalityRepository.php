<?php

namespace App\Repository;

use App\Entity\Municipality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Municipality>
 */
class MunicipalityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Municipality::class);
    }

    //    /**
    //     * @return Municipality[] Returns an array of Municipality objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Municipality
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /** @return Municipality[] */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isActive = :active')
            ->andWhere('m.isDeleted = :deleted')
            ->setParameter('active', true)
            ->setParameter('deleted', false)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return Municipality[] */
    public function findByCity(int $cityId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.city = :cityId')
            ->andWhere('m.isActive = :active')
            ->andWhere('m.isDeleted = :deleted')
            ->setParameter('cityId', $cityId)
            ->setParameter('active', true)
            ->setParameter('deleted', false)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
