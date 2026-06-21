<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Chiffre d'affaires ventilé par devise (USD, CDF…) — toutes périodes.
     * Retourne : ['USD' => 1234.0, 'CDF' => 56789.0]
     */
    public function getTotalRevenueByCurrency(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('UPPER(o.currency) as currency, SUM(o.total) as revenue')
            ->where('o.isPaid = true')
            ->andWhere('o.isDeleted = false OR o.isDeleted IS NULL')
            ->groupBy('o.currency')
            ->getQuery()
            ->getArrayResult();

        return $this->indexByCurrency($rows);
    }

    /**
     * Chiffre d'affaires ventilé par devise sur une période donnée.
     */
    public function getRevenueByCurrencyForPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('UPPER(o.currency) as currency, SUM(o.total) as revenue')
            ->where('o.isPaid = true')
            ->andWhere('o.isDeleted = false OR o.isDeleted IS NULL')
            ->andWhere('o.createdAt >= :from')
            ->andWhere('o.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('o.currency')
            ->getQuery()
            ->getArrayResult();

        return $this->indexByCurrency($rows);
    }

    /**
     * Convertit un tableau [['currency' => 'USD', 'revenue' => 1234], ...]
     * en ['USD' => 1234.0, ...]
     */
    private function indexByCurrency(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[strtoupper($row['currency'])] = (float) ($row['revenue'] ?? 0);
        }
        return $result;
    }

    /**
     * Nombre total de commandes (non supprimées)
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDeleted = false OR o.isDeleted IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre de commandes par statut de livraison
     */
    public function countByFulfillmentStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.FulfillmentStatus as status, COUNT(o.id) as total')
            ->where('o.isDeleted = false OR o.isDeleted IS NULL')
            ->groupBy('o.FulfillmentStatus')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['status']] = (int)$row['total'];
        }
        return $map;
    }

    /**
     * Nombre de commandes créées sur les N derniers jours
     */
    public function countSinceDate(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDeleted = false OR o.isDeleted IS NULL')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Revenus agrégés par jour ET par devise sur les N derniers jours (pour graphique).
     * Retourne : [['day' => 'YYYY-MM-DD', 'currency' => 'USD', 'revenue' => X, 'orders' => Y], ...]
     */
    public function getDailyRevenueByCurrencySince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('o')
            ->select("SUBSTRING(o.createdAt, 1, 10) as day, UPPER(o.currency) as currency, SUM(o.total) as revenue, COUNT(o.id) as orders")
            ->where('o.isPaid = true')
            ->andWhere('o.isDeleted = false OR o.isDeleted IS NULL')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('day, o.currency')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Dernières commandes
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isDeleted = false OR o.isDeleted IS NULL')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes en attente de livraison (confirméess + en transit)
     */
    public function countPendingFulfillment(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDeleted = false OR o.isDeleted IS NULL')
            ->andWhere('o.FulfillmentStatus IN (:statuses)')
            ->setParameter('statuses', [
                Order::FULFILLMENT_STATUS_CONFIRMED,
                Order::FULFILLMENT_STATUS_IN_TRANSIT,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
