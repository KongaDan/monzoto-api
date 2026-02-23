<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Promo;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\PromoRepository;
use App\Repository\RateRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/cart-items', name: 'api_cart_item_')]
class CartItemController extends AbstractController
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private ProductRepository $productRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em,
        private RateRepository $rateRepository
    ) {}

    /**
     * Ajouter un article au panier actif de l'utilisateur.
     * Body JSON : productId (requis), quantity (optionnel, défaut 1)
     */
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            $data      = json_decode($request->getContent(), true);
            $productId = $data['productId'] ?? null;
            $quantity  = $data['quantity'] ?? 1;

            if (!$productId) {
                return $this->apiResponse->error('Le champ productId est requis.', 400);
            }

            if ($quantity < 1) {
                return $this->apiResponse->error('La quantité doit être au moins 1.', 422);
            }

            $cart = $this->cartRepository->findActiveByUser($user->getId());
            
            if (!$cart) {
                return $this->apiResponse->error('Aucun panier actif. Créez d\'abord un panier.', 404);
            }
            $cartCurrency = $cart->getCurrency() ?? 'USD';

            $product = $this->productRepository->find($productId);

            if (!$product) {
                return $this->apiResponse->error('Produit introuvable.', 404);
            }
            $convertedPrice = $product->getPrice();

            $productCurrency = $product->getCurrency() ?? 'USD';
            if($productCurrency !== $cartCurrency){
                $rate = $this->rateRepository->findOneBy(['currencyFrom' => $productCurrency, 'currencyTo' => $cartCurrency, 'isActive' => true]);
                if(!$rate){
                    return $this->apiResponse->error('Le produit est dans une devise incompatible avec le panier.', 422);
                }

                $rateValue = $rate->getRate();
                $convertedPrice = round($product->getPrice() * $rateValue, 5);
            }

            // Vérifier si le produit est déjà dans le panier
            $item = $this->cartItemRepository->findOneBy([
                'cart' => $cart,
                'product' => $product
            ]);
            

            if ($item) {
                // Augmenter la quantité
                if($item->getUnitPrice() !== (float)$convertedPrice){
                    return $this->apiResponse->error('Le prix du produit a changé depuis la dernière fois que vous l\'avez ajouté au panier. Veuillez vérifier le panier avant de confirmer votre commande.', 422);
                }
                
                $item->setQuantity($item->getQuantity() + $quantity);
                $item->setTotalPrice($item->getQuantity() * $item->getUnitPrice());
            } else {
                // Créer un nouvel item
                $item = new CartItem();
                $item->setCart($cart);
                $item->setProduct($product);
                $item->setQuantity($quantity);
                $item->setUnitPrice($convertedPrice);
                $item->setTotalPrice($quantity * (float)$convertedPrice);

                $this->em->persist($item);
                $this->em->flush();
            }

            // Recalculer le prix total du panier
            $this->updateCartPrice($cart);

            $this->em->flush();

            return $this->apiResponse->success([
                'itemId'    => $item->getId(),
                'cartId'    => $cart->getId(),
                'productId' => $productId,
                'quantity'  => $item->getQuantity(),
                'cartTotal' => $cart->getPrice(),
            ], 'Article ajouté au panier.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Retirer une quantité spécifique d'un article du panier.
     * Body JSON : quantity (optionnel, si non fourni retirer tout l'article)
     */
    #[Route('/{itemId}', name: 'remove', methods: ['DELETE'])]
    public function remove(int $itemId, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $item = $this->cartItemRepository->find($itemId);

            if (!$item) {
                return $this->apiResponse->error('Article introuvable.', 404);
            }

            $cart = $item->getCart();

            if ($cart->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $quantityToRemove = $data['quantity'] ?? $item->getQuantity();

            if ($quantityToRemove < 1) {
                return $this->apiResponse->error('La quantité à retirer doit être au moins 1.', 422);
            }

            if ($quantityToRemove > $item->getQuantity()) {
                return $this->apiResponse->error('La quantité à retirer ne peut pas dépasser la quantité présente.', 422);
            }

            // Si la quantité à retirer est égale à la quantité présente, supprimer l'article
            if ($quantityToRemove === $item->getQuantity()) {
                $this->em->remove($item);
            } else {
                // Sinon, réduire la quantité
                $newQuantity = $item->getQuantity() - $quantityToRemove;
                $item->setQuantity($newQuantity);
                $item->setTotalPrice($newQuantity * $item->getUnitPrice());
            }

            $this->em->flush();

            // Recalculer le prix total du panier
            $this->updateCartPrice($cart);
            $this->em->flush();

            return $this->apiResponse->success(null, 'Quantité retirée du panier.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Recalcule le prix total du panier en fonction de ses articles.
     */
    private function updateCartPrice(Cart $cart): void
    {
        $total = 0.0;

        foreach ($cart->getCartItems() as $item) {
            $total += $item->getTotalPrice();
        }
        $cart->setSubPrice($total);

        $promoCode = $cart->getPromoCode();
        if ($promoCode) {
            $discountAmount = $cart->getDiscountAmount();
            $total = $total - $discountAmount;
        }

        $cart->setPrice($total);
    }
}
