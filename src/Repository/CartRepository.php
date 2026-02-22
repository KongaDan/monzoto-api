<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findActiveByUser(int $userId): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :userId')
            ->andWhere('c.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', Cart::STATUS_ACTIVE)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
