<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\OrderRepository;
use App\Repository\TransactionRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/transactions', name: 'api_transaction_')]
class TransactionController extends AbstractController
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private OrderRepository $orderRepository,
        private ApiResponse $apiResponse
    ) {}

    private function format(Transaction $t): array
    {
        return [
            'id'          => $t->getId(),
            'code'        => $t->getCode(),
            'amount'      => $t->getAmount(),
            'currency'    => $t->getCurrency(),
            'step'        => $t->getStep(),
            'provider'    => $t->getProvider(),
            'orderNumber' => $t->getOrderNumber(),
            'createdAt'   => $t->getCreatedAt()?->format('Y-m-d H:i:s'),
            'order'       => [
                'id'   => $t->getOrderCode()?->getId(),
                'code' => $t->getOrderCode()?->getCode(),
            ],
        ];
    }

    /**
     * Transactions de l'utilisateur connecté.
     */
    #[Route('/me', name: 'by_user', methods: ['GET'])]
    public function byUser(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $transactions = $this->transactionRepository->findByUser($user->getId());

            return $this->apiResponse->success(
                array_map(fn($t) => $this->format($t), $transactions),
                'Transactions de l\'utilisateur.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Transactions d'une commande précise.
     */
    #[Route('/order/{orderId}', name: 'by_order', methods: ['GET'])]
    public function byOrder(int $orderId): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $order = $this->orderRepository->find($orderId);

            if (!$order || $order->isDeleted()) {
                return $this->apiResponse->error('Commande introuvable.', 404);
            }

            if ($order->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
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

    /**
     * Détail d'une transaction précise.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $transaction = $this->transactionRepository->find($id);

            if (!$transaction) {
                return $this->apiResponse->error('Transaction introuvable.', 404);
            }

            if ($transaction->getOrderCode()?->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
            }

            return $this->apiResponse->success(
                $this->format($transaction),
                'Détail de la transaction.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
