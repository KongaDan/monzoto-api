<?php

namespace App\Controller;

use App\Entity\Wishlist;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/wishlists', name: 'api_wishlist_')]
class WishlistController extends AbstractController
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private WishlistItemRepository $wishlistItemRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    /**
     * Retourne la wishlist de l'utilisateur connecté avec tous ses items.
     */
    #[Route('', name: 'by_user', methods: ['GET'])]
    public function byUser(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $wishlist = $this->wishlistRepository->findByUser($user->getId());

            if (!$wishlist) {
                return $this->apiResponse->error('Aucune wishlist trouvée.', 404);
            }

            $items = $this->wishlistItemRepository->findByWishlist($wishlist->getId());

            $data = [
                'id'        => $wishlist->getId(),
                'createdAt' => $wishlist->getCreatedAt()?->format('Y-m-d H:i:s'),
                'items'     => array_map(fn($item) => [
                    'id'        => $item->getId(),
                    'createdAt' => $item->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'product'   => [
                        'id'       => $item->getProduct()?->getId(),
                        'name'     => $item->getProduct()?->getName(),
                        'price'    => $item->getProduct()?->getPrice(),
                        'currency' => $item->getProduct()?->getCurrency(),
                        'picture'  => $item->getProduct()?->getPicture(),
                    ],
                ], $items),
            ];

            return $this->apiResponse->success($data, 'Wishlist de l\'utilisateur.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Crée une wishlist pour l'utilisateur connecté.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $existing = $this->wishlistRepository->findByUser($user->getId());
            if ($existing) {
                return $this->apiResponse->error('Vous possédez déjà une wishlist.', 409);
            }

            $wishlist = new Wishlist();
            $wishlist->setCustomer($user);

            $this->em->persist($wishlist);
            $this->em->flush();

            return $this->apiResponse->success([
                'id'        => $wishlist->getId(),
                'createdAt' => $wishlist->getCreatedAt()?->format('Y-m-d H:i:s'),
            ], 'Wishlist créée avec succès.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
