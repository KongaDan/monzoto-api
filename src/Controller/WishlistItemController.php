<?php

namespace App\Controller;

use App\Entity\WishlistItem;
use App\Repository\ProductRepository;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/wishlist-items', name: 'api_wishlist_item_')]
class WishlistItemController extends AbstractController
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private WishlistItemRepository $wishlistItemRepository,
        private ProductRepository $productRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    /**
     * Ajoute un produit à la wishlist de l'utilisateur connecté.
     * Body: { "productId": 5 }
     */
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $data      = json_decode($request->getContent(), true);
            $productId = $data['productId'] ?? null;

            if (!$productId) {
                return $this->apiResponse->error('Le champ productId est requis.', 400);
            }

            $wishlist = $this->wishlistRepository->findByUser($user->getId());
            if (!$wishlist) {
                return $this->apiResponse->error('Aucune wishlist trouvée pour cet utilisateur.', 404);
            }

            $product = $this->productRepository->find($productId);
            if (!$product) {
                return $this->apiResponse->error('Produit introuvable.', 404);
            }

            $existing = $this->wishlistItemRepository->findExistingItem($wishlist->getId(), $productId);
            if ($existing) {
                return $this->apiResponse->error('Ce produit est déjà dans la wishlist.', 409);
            }

            $item = new WishlistItem();
            $item->setWishlist($wishlist);
            $item->setProduct($product);

            $this->em->persist($item);
            $this->em->flush();

            return $this->apiResponse->success([
                'id'        => $item->getId(),
                'createdAt' => $item->getCreatedAt()?->format('Y-m-d H:i:s'),
                'product'   => [
                    'id'       => $product->getId(),
                    'name'     => $product->getName(),
                    'price'    => $product->getPrice(),
                    'currency' => $product->getCurrency(),
                    'picture'  => $product->getPicture(),
                ],
            ], 'Produit ajouté à la wishlist.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Retire un item de la wishlist de l'utilisateur connecté.
     */
    #[Route('/{id}', name: 'remove', methods: ['DELETE'])]
    public function remove(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $item = $this->wishlistItemRepository->find($id);

            if (!$item) {
                return $this->apiResponse->error('Item introuvable.', 404);
            }

            if ($item->getWishlist()?->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Action non autorisée.', 403);
            }

            $this->em->remove($item);
            $this->em->flush();

            return $this->apiResponse->success(null, 'Produit retiré de la wishlist.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}

