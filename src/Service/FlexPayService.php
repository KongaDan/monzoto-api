<?php

namespace App\Service;

use App\Service\ApiCall;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class FlexPayService
{
    private string $merchant;
    private string $token;
    private string $callbackUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $appMerchant,
        string $appToken,
        string $appCallbackUrl
    ) {
        $this->merchant    = $appMerchant;
        $this->token       = $appToken;
        $this->callbackUrl = $appCallbackUrl;
    }

    /**
     * Paiement Mobile Money
     * Renvoie ['success'=>bool, 'orderNumber'=>string|null, 'message'=>string, 'raw'=>array]
     */
    public function payMobile(
        string $reference,
        string $phone,
        float  $amount,
        string $currency
    ): array {
        $payload = [
            'merchant'    => $this->merchant,
            'type'        => 1,
            'reference'   => $reference,
            'phone'       => $phone,
            'amount'      => $amount,
            'currency'    => $currency,
            'callbackUrl' => $this->callbackUrl,
        ];

        try {
            $response = $this->httpClient->request('POST', ApiCall::MOBILE_PAYMENT_GATEWAY, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);
            $this->logger->info('FlexPay mobile response', $data);

            $success = isset($data['code']) && $data['code'] === '0';

            return [
                'success'     => $success,
                'orderNumber' => $data['orderNumber'] ?? null,
                'message'     => $data['message'] ?? ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => $data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('FlexPay mobile error: ' . $e->getMessage());
            return [
                'success'     => false,
                'orderNumber' => null,
                'message'     => ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => [],
            ];
        }
    }

    /**
     * Paiement par carte bancaire
     * Renvoie ['success'=>bool, 'orderNumber'=>string|null, 'url'=>string|null, 'message'=>string, 'raw'=>array]
     */
    public function payCard(
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $approveUrl = '',
        string $cancelUrl  = '',
        string $declineUrl = ''
    ): array {
        $payload = [
            'authorization' => 'Bearer ' . $this->token,
            'merchant'      => $this->merchant,
            'reference'     => $reference,
            'amount'        => $amount,
            'currency'      => $currency,
            'description'   => $description,
            'callback_url'  => $this->callbackUrl,
            'approve_url'   => $approveUrl  ?: $this->callbackUrl,
            'cancel_url'    => $cancelUrl   ?: $this->callbackUrl,
            'decline_url'   => $declineUrl  ?: $this->callbackUrl,
        ];

        try {
            $response = $this->httpClient->request('POST', ApiCall::CARD_PAYMENT_GATEWAY, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);
            $this->logger->info('FlexPay card response', $data);

            $success = isset($data['code']) && $data['code'] === '0';

            return [
                'success'     => $success,
                'orderNumber' => $data['orderNumber'] ?? null,
                'url'         => $data['url'] ?? null,
                'message'     => $data['message'] ?? ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => $data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('FlexPay card error: ' . $e->getMessage());
            return [
                'success'     => false,
                'orderNumber' => null,
                'url'         => null,
                'message'     => ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => [],
            ];
        }
    }

    /**
     * Vérifier le statut d'une transaction via son orderNumber
     * Renvoie ['success'=>bool, 'status'=>string|null, 'transaction'=>array|null, 'message'=>string]
     */
    public function checkTransaction(string $orderNumber): array
    {
        try {
            $url = ApiCall::CHECK_TRANSACTION . '/' . $orderNumber;

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);

            $data = $response->toArray(false);
            $this->logger->info('FlexPay check response', $data);

            $success = isset($data['code']) && $data['code'] === '0';

            return [
                'success'     => $success,
                'status'      => $data['transaction']['status'] ?? null,
                'transaction' => $data['transaction'] ?? null,
                'message'     => $data['message'] ?? ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => $data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('FlexPay check error: ' . $e->getMessage());
            return [
                'success'     => false,
                'status'      => null,
                'transaction' => null,
                'message'     => ApiCall::GENERIC_FAIL_MESSAGE,
                'raw'         => [],
            ];
        }
    }
}
