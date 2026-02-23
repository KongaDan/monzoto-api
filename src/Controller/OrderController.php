<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\CartRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\RateRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/order', name: 'app_order_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApiResponse $apiResponse,
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private CartRepository $cartRepository,
        private AddressRepository $addressRepository,
        private RateRepository $rateRepository
    ) {}

    private function getUserEntity(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }


    /**
     * Créer une commande à partir du panier actif de l'utilisateur
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            // Récupérer le panier actif de l'utilisateur
            $cart = $this->cartRepository->findActiveByUser($user->getId());
            
            if (!$cart) {
                return $this->apiResponse->error('Aucun panier actif trouvé', 404);
            }

            if ($cart->getCartItems()->isEmpty()) {
                return $this->apiResponse->error('Le panier est vide', 400);
            }
            
            // Créer la commande
            $order = new Order();
            $order->setCustomer($user);
            $order->setCart($cart);
            $order->setCurrency($cart->getCurrency());
            
            // Calculer les totaux
            $subTotal = $cart->getSubPrice() ?? $cart->getPrice();
            $order->setSubTotal($subTotal);
            
            // Appliquer les remises si présentes
            $discountTotal = $cart->getDiscountAmount() ?? 0;
            $order->setDiscountTotal($discountTotal);
        
            
            // Calculer le total final
            $total = $subTotal - $discountTotal;
            $order->setTotal($total);
            
            
            $this->em->persist($order);
            
            // Créer les OrderItem à partir des CartItem
            foreach ($cart->getCartItems() as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrderCode($order);
                $orderItem->setProductName($cartItem->getProduct()->getName());
                $orderItem->setProductCode($cartItem->getProduct()->getReference() ?? '');
                $orderItem->setProductPrice($cartItem->getUnitPrice());
                $orderItem->setProductQuantity($cartItem->getQuantity());
                $orderItem->setTotalPrice($cartItem->getTotalPrice());
                
                $this->em->persist($orderItem);
            }
            
            // Marquer le panier comme converti
            $cart->setStatus(Cart::STATUS_CONVERTED);
            
            $this->em->flush();
            
            return $this->apiResponse->success(
                $order,
                'Commande créée avec succès',
                201,
                ['order:read']
            );
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }

    #[Route('/{id}/address', name: 'address', methods: ['PATCH'])]
    public function addressOrder(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            $order = $this->orderRepository->find($id);
            
            if (!$order) {
                return $this->apiResponse->error('Commande non trouvée', 404);
            }

            // Vérifier que la commande appartient à l'utilisateur
            if ($order->getCustomer()->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé', 403);
            }

            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);

            if(!isset($data['addressId'])){
                return $this->apiResponse->error('Modification de l\'adresse non implémentée', 400, 'addressId manquant');
            }
            
            $address = $this->addressRepository->find($data['addressId'] ?? null);
            if (!$address) {
                return $this->apiResponse->error('Adresse non trouvée', 404);
            }
            $firstname = $address->getFirstname() ? $address->getFirstname() : $user->getFirstName();
            $lastname = $address->getLastname() ? $address->getLastname() : $user->getLastName();
            $phone = $address->getPhone() ? $address->getPhone() : $user->getPhone();
            $description = $address->getDescription();
            $municipality = $address->getMunicipality();
            $amount = $municipality->getShippingCost() ?? 0.00;
            $currency = $municipality->getCurrency() ?? $order->getCurrency();
            $isShippingAvailable = $municipality->isShippingAvailable();

            if (!$isShippingAvailable) {
                return $this->apiResponse->error('La livraison n\'est pas disponible pour cette adresse', 400);
            }
            $currencyOrder = $order->getCurrency();

            if($currency !== $currencyOrder){
                
                $exchangeRate = $this->rateRepository->findActiveRate($currency, $currencyOrder);
                if (!$exchangeRate) {
                    return $this->apiResponse->error('Taux de change non trouvé pour ' . $currency . ' vers ' . $currencyOrder, 400);
                }
                $amount = $amount * $exchangeRate->getRate();
            }

            $olderShippingCost = $order->getShippingCost() ?? 0.00;
            $newAmountValue = $amount - $olderShippingCost;

            $order->setShippingCost($amount);
            $order->setCurrencyShipping($currencyOrder);


            $total = $order->getTotal() + $newAmountValue;
            $order->setTotal($total);

            // Informations client
            if($user->getEmail()) {
                $order->setCustomerEmail($user->getEmail());
            }
            if(isset($firstname)) {
                $order->setCustomerFirstName($firstname);
            }
            if(isset($lastname)) {
                $order->setCustomerLastName($lastname);
            }
            if(isset($phone)) {
                $order->setCustomerPhone($phone);
            }
            if(isset($description)) {
                $order->setCustomerAddress($description);
            }
                        
            $this->em->flush();
            
            return $this->apiResponse->success(
                $order,
                'Commande mise à jour avec succès',
                200,
                ['order:read']
            );
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }

    /**
     * Annuler une commande
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['PATCH'])]
    public function cancelOrder(int $id): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            $order = $this->orderRepository->find($id);
            
            if (!$order) {
                return $this->apiResponse->error('Commande non trouvée', 404);
            }

            // Vérifier que la commande appartient à l'utilisateur
            if ($order->getCustomer()->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé', 403);
            }

            // Vérifier si la commande peut être annulée
            if ($order->getFulfillmentStatus() === Order::FULFILLMENT_STATUS_DELIVERED) {
                return $this->apiResponse->error('Impossible d\'annuler une commande déjà livrée', 400);
            }

            if ($order->getFulfillmentStatus() === Order::FULFILLMENT_STATUS_CANCELLED) {
                return $this->apiResponse->error('Cette commande est déjà annulée', 400);
            }

            // Annuler la commande
            $order->setFulfillmentStatus(Order::FULFILLMENT_STATUS_CANCELLED);
            $order->setPaymentStatus(Order::PAYMENT_STATUS_CANCEL);
            $order->setIsDeleted(true);
            
            $this->em->flush();
            
            return $this->apiResponse->success(
                $order,
                'Commande annulée avec succès',
                200,
                ['order:read']
            );
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }

    /**
     * Ajouter une note à une commande
     */
    #[Route('/{id}/note', name: 'add_note', methods: ['PATCH'])]
    public function addNote(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            $order = $this->orderRepository->find($id);
            
            if (!$order) {
                return $this->apiResponse->error('Commande non trouvée', 404);
            }

            // Vérifier que la commande appartient à l'utilisateur
            if ($order->getCustomer()->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé', 403);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['note'])) {
                return $this->apiResponse->error('La note est requise', 400);
            }

            $order->setNotes($data['note']);
            
            $this->em->flush();
            
            return $this->apiResponse->success(
                ['orderId' => $order->getId()],
                'Note ajoutée avec succès'
            );
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }

    /**
     * Liste des commandes de l'utilisateur avec pagination
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            // Récupérer les paramètres de pagination
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = max(1, min(100, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            // Construire la requête
            $queryBuilder = $this->orderRepository->createQueryBuilder('o')
                ->where('o.customer = :user')
                ->andWhere('o.isDeleted IS NULL OR o.isDeleted = false')
                ->andWhere('o.FulfillmentStatus != :cancelled')
                ->setParameter('user', $user)
                ->setParameter('cancelled', Order::FULFILLMENT_STATUS_CANCELLED)
                ->orderBy('o.createdAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit);

            $orders = $queryBuilder->getQuery()->getResult();

            // Compter le total
            $totalQueryBuilder = $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.customer = :user')
                ->andWhere('o.isDeleted IS NULL OR o.isDeleted = false')
                ->andWhere('o.FulfillmentStatus != :cancelled')
                ->setParameter('user', $user)
                ->setParameter('cancelled', Order::FULFILLMENT_STATUS_CANCELLED);

            $total = (int) $totalQueryBuilder->getQuery()->getSingleScalarResult();

            // Formater les données
            $ordersData = array_map(function (Order $order) {
                return [
                    'id' => $order->getId(),
                    'code' => $order->getCode(),
                    'total' => $order->getTotal(),
                    'currency' => $order->getCurrency(),
                    'paymentStatus' => $order->getPaymentStatus(),
                    'fulfillmentStatus' => $order->getFulfillmentStatus(),
                    'isPaid' => $order->isPaid(),
                    'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'deliveredAt' => $order->getDeliveredAt()?->format('Y-m-d H:i:s'),
                ];
            }, $orders);

            return $this->apiResponse->success([
                'orders' => $ordersData,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => ceil($total / $limit),
                    'totalItems' => $total,
                    'itemsPerPage' => $limit,
                ]
            ], 'Liste des commandes récupérée avec succès');
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }

    /**
     * Détails d'une commande
     */
    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function details(int $id): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié', 401);
            }

            $order = $this->orderRepository->find($id);
            
            if (!$order) {
                return $this->apiResponse->error('Commande non trouvée', 404);
            }

            // Vérifier que la commande appartient à l'utilisateur
            if ($order->getCustomer()->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé', 403);
            }

            // Récupérer les items de la commande
            $orderItems = $this->orderItemRepository->findBy(['orderCode' => $order]);

            $orderItemsData = array_map(function (OrderItem $item) {
                return [
                    'id' => $item->getId(),
                    'productName' => $item->getProductName(),
                    'productCode' => $item->getProductCode(),
                    'productPrice' => $item->getProductPrice(),
                    'productQuantity' => $item->getProductQuantity(),
                    'totalPrice' => $item->getTotalPrice(),
                ];
            }, $orderItems);

            $orderData = [
                'id' => $order->getId(),
                'code' => $order->getCode(),
                'subTotal' => $order->getSubTotal(),
                'discountTotal' => $order->getDiscountTotal(),
                'shippingCost' => $order->getShippingCost(),
                'currencyShipping' => $order->getCurrencyShipping(),
                'total' => $order->getTotal(),
                'currency' => $order->getCurrency(),
                'paymentStatus' => $order->getPaymentStatus(),
                'fulfillmentStatus' => $order->getFulfillmentStatus(),
                'paymentMethod' => $order->getPaymentMethod(),
                'isPaid' => $order->isPaid(),
                'customerEmail' => $order->getCustomerEmail(),
                'customerFirstName' => $order->getCustomerFirstName(),
                'customerLastName' => $order->getCustomerLastName(),
                'customerPhone' => $order->getCustomerPhone(),
                'customerAddress' => $order->getCustomerAddress(),
                'notes' => $order->getNotes(),
                'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                'deliveredAt' => $order->getDeliveredAt()?->format('Y-m-d H:i:s'),
                'items' => $orderItemsData,
            ];

            return $this->apiResponse->success($orderData, 'Détails de la commande récupérés avec succès');
            
        } catch (\Exception $e) {
            return $this->apiResponse->error($e->getMessage(), 500);
        }
    }
}
