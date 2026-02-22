<?php

namespace App\Repository;

use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /** @return CartItem[] */
    public function findByCart(int $cartId): array
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.cart = :cartId')
            ->setParameter('cartId', $cartId)
            ->orderBy('ci.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
