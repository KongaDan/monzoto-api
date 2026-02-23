<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint PUBLIC appelé par FlexPay après une transaction.
 * Route : /api/payment/callback (non sécurisé par JWT)
 */
#[Route('/api/payment', name: 'api_payment_')]
class PaymentController extends AbstractController
{
    public function __construct(
        private TransactionRepository  $transactionRepository,
        private EntityManagerInterface $em,
        private LoggerInterface        $logger
    ) {}

    /**
     * Callback FlexPay
     * POST /api/payment/callback
     *
     * Payload attendu :
     * {
     *   "code": "0",           // 0 = succès, 1 = échec
     *   "reference": "...",
     *   "amount": "100.0",
     *   "currency": "CDF",
     *   "orderNumber": "...",  // identifiant FlexPay unique
     *   "provider_reference": "...",
     *   "channel": "mpesa|visa|...",
     *   "phone": "...",
     *   "createdAt": "dd-mm-yyyy HH:ii:ss"
     * }
     */
    #[Route('/callback', name: 'callback', methods: ['POST'])]
    public function callback(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('FlexPay callback received', $data ?? []);

        if (!$data || !isset($data['orderNumber'])) {
            return new Response('Bad Request', 400);
        }

        $orderNumber = $data['orderNumber'];
        $code        = $data['code'] ?? '1';
        $channel    = $data['channel'] ;

        // Trouver la transaction locale
        $transaction = $this->transactionRepository->findOneBy(['orderNumber' => $orderNumber]);

        if (!$transaction) {
            $this->logger->warning('FlexPay callback: transaction introuvable pour orderNumber=' . $orderNumber);
            // On retourne 200 quand même pour éviter les retries FlexPay
            return new Response('OK', 200);
        }

        $order = $transaction->getOrderCode();
        $transaction->setProvider($channel);

        if ($code === '0') {
            // ── SUCCÈS ──────────────────────────────────────────────────────
            $transaction->setStep((string) Transaction::SUCCESS);

            if ($order && !$order->isPaid()) {
                $order->setIsPaid(true);
                $order->setPaymentStatus(Order::PAYMENT_STATUS_PAID);
                $order->setFulfillmentStatus(Order::FULFILLMENT_STATUS_CONFIRMED);
            }

            $this->logger->info('FlexPay callback: paiement validé pour orderNumber=' . $orderNumber);
        } else {
            // ── ÉCHEC ────────────────────────────────────────────────────────
            $transaction->setStep((string) Transaction::CANCEL);

            if ($order) {
                $order->setPaymentStatus(Order::PAYMENT_STATUS_FAILED);
            }

            $this->logger->warning('FlexPay callback: paiement échoué pour orderNumber=' . $orderNumber);
        }

        // Stocker la référence du provider si présente
        if (!empty($data['provider_reference'])) {
            $transaction->setProviderReference($data['provider_reference']);
        }

        $this->em->flush();

        return new Response('OK', 200);
    }
}
