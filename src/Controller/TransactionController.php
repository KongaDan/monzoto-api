<?php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\TransactionRepository;
use App\Service\ApiResponse;
use App\Service\FlexPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/transactions', name: 'api_transaction_')]
class TransactionController extends AbstractController
{
    public function __construct(
        private TransactionRepository  $transactionRepository,
        private OrderRepository        $orderRepository,
        private ApiResponse            $apiResponse,
        private FlexPayService         $flexPayService,
        private EntityManagerInterface $em
    ) {}

    private function getUserEntity(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    private function format(Transaction $t): array
    {
        return [
            'id'                => $t->getId(),
            'code'              => $t->getCode(),
            'amount'            => $t->getAmount(),
            'currency'          => $t->getCurrency(),
            'step'              => $t->getStep(),
            'provider'          => $t->getProvider(),
            'orderNumber'       => $t->getOrderNumber(),
            'paymentUrl'        => $t->getPaymentUrl(),
            'providerReference' => $t->getProviderReference(),
            'createdAt'         => $t->getCreatedAt()?->format('Y-m-d H:i:s'),
            'order'             => [
                'id'   => $t->getOrderCode()?->getId(),
                'code' => $t->getOrderCode()?->getCode(),
            ],
        ];
    }

    /** Vérifier que la commande appartient à l'utilisateur connecté. */
    private function resolveOrder(int $orderId, User $user): Order|JsonResponse
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order || $order->isDeleted()) {
            return $this->apiResponse->error('Commande introuvable.', 404);
        }

        if ($order->getCustomer()?->getId() !== $user->getId()) {
            return $this->apiResponse->error('Acces non autorise.', 403);
        }

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAIEMENT MOBILE MONEY
    // POST /api/secure/transactions/pay/mobile/{orderId}
    // Body JSON : { "phone": "243817877848", "currency": "CDF" }
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/pay/mobile/{orderId}', name: 'pay_mobile', methods: ['POST'])]
    public function payMobile(int $orderId, Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $order = $this->resolveOrder($orderId, $user);
            if ($order instanceof JsonResponse) {
                return $order;
            }
            $order->setPaymentMethod(Order::PAYMENT_METHOD_MOBILE_MONEY);

            if ($order->isPaid()) {
                return $this->apiResponse->error('Cette commande est deja payee.', 400);
            }

            $data     = json_decode($request->getContent(), true) ?? [];
            $phone    = $data['phone'] ?? null;
            $currency = $order->getCurrency();

            if (!$phone) {
                return $this->apiResponse->error('Le numero de telephone est requis.', 400);
            }

            $reference = $order->getCode() . '_' . time();

            $result = $this->flexPayService->payMobile(
                $reference,
                $phone,
                (float) $order->getTotal(),
                $currency
            );

            $transaction = new Transaction();
            $transaction->setOrderCode($order);
            $transaction->setCode(bin2hex(random_bytes(8)));
            $transaction->setAmount((string) $order->getTotal());
            $transaction->setCurrency($currency);
            $transaction->setProvider('mobile_money');
            $transaction->setStep((string) Transaction::PENDING);
            $transaction->setOrderNumber($result['orderNumber'] ?? '');

            $this->em->persist($transaction);
            $this->em->flush();

            if (!$result['success']) {
                $transaction->setStep((string) Transaction::CANCEL);
                $this->em->flush();
                return $this->apiResponse->error($result['message'], 400);
            }

            return $this->apiResponse->success(
                [
                    'transactionId' => $transaction->getId(),
                    'orderNumber'   => $result['orderNumber'],
                    'message'       => $result['message'],
                ],
                'Demande de paiement mobile envoyee avec succes.',
                201
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAIEMENT PAR CARTE BANCAIRE
    // POST /api/secure/transactions/pay/card/{orderId}
    // Body JSON : { "currency": "CDF", "description": "...",
    //               "approveUrl": "...", "cancelUrl": "...", "declineUrl": "..." }
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/pay/card/{orderId}', name: 'pay_card', methods: ['POST'])]
    public function payCard(int $orderId, Request $request): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $order = $this->resolveOrder($orderId, $user);
            if ($order instanceof JsonResponse) {
                return $order;
            }
            $order->setPaymentMethod(Order::PAYMENT_METHOD_CREDIT_CARD);

            if ($order->isPaid()) {
                return $this->apiResponse->error('Cette commande est deja payee.', 400);
            }

            $data        = json_decode($request->getContent(), true) ?? [];
            $currency    = $order->getCurrency();
            $description = $data['description'] ?? 'Paiement commande ' . $order->getCode();
            $approveUrl  = $data['approveUrl']  ?? '';
            $cancelUrl   = $data['cancelUrl']   ?? '';
            $declineUrl  = $data['declineUrl']  ?? '';

            $reference = $order->getCode() . '_' . time();

            $result = $this->flexPayService->payCard(
                $reference,
                (float) $order->getTotal(),
                $currency,
                $description,
                $approveUrl,
                $cancelUrl,
                $declineUrl
            );

            $transaction = new Transaction();
            $transaction->setOrderCode($order);
            $transaction->setCode(bin2hex(random_bytes(8)));
            $transaction->setAmount((string) $order->getTotal());
            $transaction->setCurrency($currency);
            $transaction->setProvider('card');
            $transaction->setStep((string) Transaction::PENDING);
            $transaction->setOrderNumber($result['orderNumber'] ?? '');
            $transaction->setPaymentUrl($result['url'] ?? null);

            $this->em->persist($transaction);
            $this->em->flush();

            if (!$result['success']) {
                $transaction->setStep((string) Transaction::CANCEL);
                $this->em->flush();
                return $this->apiResponse->error($result['message'], 400);
            }

            return $this->apiResponse->success(
                [
                    'transactionId' => $transaction->getId(),
                    'orderNumber'   => $result['orderNumber'],
                    'paymentUrl'    => $result['url'],
                    'message'       => $result['message'],
                ],
                'Lien de paiement par carte genere.',
                201
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VÉRIFIER LE STATUT D'UNE TRANSACTION
    // GET /api/secure/transactions/check/{orderNumber}
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/check/{orderNumber}', name: 'check', methods: ['GET'])]
    public function check(string $orderNumber): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $transaction = $this->transactionRepository->findOneBy(['orderNumber' => $orderNumber]);
            if (!$transaction) {
                return $this->apiResponse->error('Transaction introuvable.', 404);
            }

            if ($transaction->getOrderCode()?->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Acces non autorise.', 403);
            }

            $result = $this->flexPayService->checkTransaction($orderNumber);

            if ($result['success'] && isset($result['status'])) {
                $remoteStatus = $result['status'];

                if ($remoteStatus === '0') {
                    $transaction->setStep((string) Transaction::SUCCESS);
                    $order = $transaction->getOrderCode();
                    if ($order && !$order->isPaid()) {
                        $order->setIsPaid(true);
                        $order->setPaymentStatus(Order::PAYMENT_STATUS_PAID);
                    }
                } elseif (in_array($remoteStatus, ['3', '4'], true)) {
                    $transaction->setStep((string) Transaction::CANCEL);
                }
                $this->em->flush();
            }

            return $this->apiResponse->success(
                [
                    'transaction' => $this->format($transaction),
                    'remote'      => $result['transaction'],
                ],
                $result['message']
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSACTIONS DE L'UTILISATEUR CONNECTÉ
    // GET /api/secure/transactions/me
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/me', name: 'by_user', methods: ['GET'])]
    public function byUser(): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $transactions = $this->transactionRepository->findByUser($user->getId());

            return $this->apiResponse->success(
                array_map(fn($t) => $this->format($t), $transactions),
                "Transactions de l'utilisateur."
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSACTIONS D'UNE COMMANDE
    // GET /api/secure/transactions/order/{orderId}
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/order/{orderId}', name: 'by_order', methods: ['GET'])]
    public function byOrder(int $orderId): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $order = $this->resolveOrder($orderId, $user);
            if ($order instanceof JsonResponse) {
                return $order;
            }

            $transactions = $this->transactionRepository->findByOrder($orderId);

            return $this->apiResponse->success(
                array_map(fn($t) => $this->format($t), $transactions),
                'Transactions de la commande.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DÉTAIL D'UNE TRANSACTION
    // GET /api/secure/transactions/{id}
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUserEntity();
            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifie.', 401);
            }

            $transaction = $this->transactionRepository->find($id);
            if (!$transaction) {
                return $this->apiResponse->error('Transaction introuvable.', 404);
            }

            if ($transaction->getOrderCode()?->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Acces non autorise.', 403);
            }

            return $this->apiResponse->success($this->format($transaction), 'Detail de la transaction.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
