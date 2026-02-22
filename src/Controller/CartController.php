<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Promo;
use App\Repository\CartRepository;
use App\Repository\PromoRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/carts', name: 'api_cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private CartRepository $cartRepository,
        private PromoRepository $promoRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    private function format(Cart $cart): array
    {
        $items = $cart->getCartItems()->map(fn($item) => [
            'id'         => $item->getId(),
            'quantity'   => $item->getQuantity(),
            'unitPrice'  => $item->getUnitPrice(),
            'totalPrice' => $item->getTotalPrice(),
            'product'    => [
                'id'    => $item->getProduct()?->getId(),
                'name'  => $item->getProduct()?->getName(),
                'price' => $item->getProduct()?->getPrice(),
            ],
        ])->toArray();

        return [
            'id'            => $cart->getId(),
            'status'        => $cart->getStatus(),
            'subPrice'      => $cart->getSubPrice(),
            'discountAmount'=> $cart->getDiscountAmount(),
            'price'         => $cart->getPrice(),
            'currency'      => $cart->getCurrency(),
            'promoCode'     => $cart->getPromoCode(),
            'itemCount'     => count($items),
            'items'         => $items,
            'updatedAt'     => $cart->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function recalcSubPrice(Cart $cart): float
    {
        $total = 0.0;

        foreach ($cart->getCartItems() as $item) {
            $total += $item->getTotalPrice();
        }

        $cart->setSubPrice($total);

        return $total;
    }

    private function createActiveCartForUser($user): Cart
    {
        $cart = new Cart();
        $cart->setCustomer($user);
        $cart->setStatus(Cart::STATUS_ACTIVE);
        $cart->setPrice(0.0);
        $cart->setSubPrice(0.0);
        
        $preferedCurrency = $user->getCurrency() ?: 'USD';
        $cart->setCurrency($preferedCurrency);

        $this->em->persist($cart);

        return $cart;
    }


    /**
     * Obtenir le panier actif de l'utilisateur connecté.
     */
    #[Route('/active', name: 'get_active', methods: ['GET'])]
    public function getActive(): JsonResponse
    {
        try {
            $user = $this->getUser();

            $cart = $this->cartRepository->findActiveByUser($user->getId());

            if (!$cart) {
                $cart = $this->createActiveCartForUser($user);
                $this->em->flush();
            }

            return $this->apiResponse->success($this->format($cart), 'Panier actif.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }


    /**
     * Abandonner le panier actif de l'utilisateur connecté.
     */
    #[Route('/active/abandon', name: 'abandon', methods: ['POST'])]
    public function abandon(): JsonResponse
    {
        try {
            $user = $this->getUser();


            $cart = $this->cartRepository->findActiveByUser($user->getId());

            if (!$cart) {
                return $this->apiResponse->error('Aucun panier actif trouvé.', 404);
            }

            $cart->setStatus(Cart::STATUS_ABANDONED);
            $newCart = $this->createActiveCartForUser($user);

            $this->em->flush();

            return $this->apiResponse->success(
                [
                    'abandonedCartId' => $cart->getId(),
                    'newCartId' => $newCart->getId(),
                ],
                'Panier abandonné.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Convertir le panier actif en commande (change status en CONVERTED).
     */
    #[Route('/active/convert', name: 'convert', methods: ['POST'])]
    public function convert(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $cart = $this->cartRepository->findActiveByUser($user->getId());

            if (!$cart) {
                return $this->apiResponse->error('Aucun panier actif trouvé.', 404);
            }

            if (count($cart->getCartItems()) === 0) {
                return $this->apiResponse->error('Le panier est vide.', 422);
            }

            $cart->setStatus(Cart::STATUS_CONVERTED);
            $newCart = $this->createActiveCartForUser($user);

            $this->em->flush();

            return $this->apiResponse->success(
                [
                    'convertedCart' => $this->format($cart),
                    'newCartId' => $newCart->getId(),
                ],
                'Panier converti en commande.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Appliquer un code promo sur le panier actif de l'utilisateur.
     * Body JSON : code (requis)
     */
    #[Route('/apply/promo', name: 'apply_promo', methods: ['POST'])]
    public function applyPromo(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            $cart = $this->cartRepository->findActiveByUser($user->getId());

            if (!$cart) {
                return $this->apiResponse->error('Aucun panier actif. Créez d\'abord un panier.', 409);
            }

            $data = json_decode($request->getContent(), true);
            $code = $data['code'] ?? null;

            if (!$code) {
                return $this->apiResponse->error('Le champ code est requis.', 400);
            }

            $promo = $this->promoRepository->findActiveByCode($code);

            if (!$promo) {
                return $this->apiResponse->error('Code promo invalide ou expiré.', 404);
            }

            if ($promo->getCurrency() !== $cart->getCurrency()) {
                return $this->apiResponse->error('Currency du code promo invalide.', 422);
            }

            $subPrice = $this->recalcSubPrice($cart);

            if ($subPrice < $promo->getMinOrderAmount() || $subPrice > $promo->getMaxOrderAmount()) {
                return $this->apiResponse->error('Montant du panier hors limites du code promo.', 422);
            }

            if ($promo->getType() === Promo::TYPE_AMOUNT) {
                $discount = min($promo->getValue(), $subPrice);
            } else {
                $discount = $subPrice * ($promo->getValue() / 100);
                $discount = min($discount, $subPrice);
            }

            $cart->setPromoCode($promo->getCode());
            $cart->setDiscountAmount($discount);
            $cart->setPrice(max(0.0, $subPrice - $discount));

            $this->em->flush();

            return $this->apiResponse->success(
                $this->format($cart),
                'Code promo appliqué.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
