<?php

namespace App\Repository;

use App\Entity\Promo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promo>
 */
class PromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promo::class);
    }

    //    /**
    //     * @return Promo[] Returns an array of Promo objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Promo
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findActiveByCode(string $code): ?Promo
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.validFrom <= :now')
            ->andWhere('p.validTo >= :now')
            ->andWhere('p.usageCount < p.usageLimit')
            ->setParameter('code', $code)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
