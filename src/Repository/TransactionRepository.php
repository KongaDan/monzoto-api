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

    public function records(array $criteria = []){
        $qb = $this->createQueryBuilder('t');
        if(isset($criteria['step']) && in_array($criteria['step'], [1,2,3,5])) {

            if($criteria['step'] == 1 || $criteria['step'] == 2){
                $qb->andWhere('t.step IN (:steps)')
                    ->setParameter('steps', [1,2]);
            }else {
                $qb->andWhere('t.step = :step')
                    ->setParameter('step', $criteria['step']);
            }
        }
        return $qb->orderBy('t.id', 'DESC')->getQuery()->getResult();
    }

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
