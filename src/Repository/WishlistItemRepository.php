<?php

namespace App\Repository;

use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    /** @return WishlistItem[] */
    public function findByWishlist(int $wishlistId): array
    {
        return $this->createQueryBuilder('wi')
            ->andWhere('wi.wishlist = :wishlistId')
            ->setParameter('wishlistId', $wishlistId)
            ->orderBy('wi.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findExistingItem(int $wishlistId, int $productId): ?WishlistItem
    {
        return $this->createQueryBuilder('wi')
            ->andWhere('wi.wishlist = :wishlistId')
            ->andWhere('wi.product = :productId')
            ->setParameter('wishlistId', $wishlistId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
