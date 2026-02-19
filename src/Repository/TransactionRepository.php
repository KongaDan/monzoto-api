<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /** @return Transaction[] */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.orderCode', 'o')
            ->andWhere('o.customer = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return Transaction[] */
    public function findByOrder(int $orderId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.orderCode = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
