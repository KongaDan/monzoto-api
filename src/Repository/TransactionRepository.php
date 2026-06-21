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

    /**
     * Nombre de transactions par statut
     */
    public function countByStep(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.step as step, COUNT(t.id) as total')
            ->groupBy('t.step')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['step']] = (int)$row['total'];
        }
        return $map;
    }

    /**
     * Taux de succès des paiements (transactions réussies / total)
     */
    public function getSuccessRate(): float
    {
        $total = (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            return 0.0;
        }

        $success = (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.step = :step')
            ->setParameter('step', \App\Entity\Transaction::SUCCESS)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($success / $total) * 100, 1);
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
