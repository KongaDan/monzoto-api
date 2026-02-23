<?php 

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiCall
{
    public HttpClientInterface $httpClient;
    public LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient_,
        LoggerInterface $logger_
    ) {
        $this->httpClient = $httpClient_;
        $this->logger = $logger_;
    }

    # API ENDPOINTS
    const CARD_PAYMENT_GATEWAY = "https://cardpayment.flexpay.cd/v1.1/pay";
    const MOBILE_PAYMENT_GATEWAY = "https://backend.flexpay.cd/api/rest/v1/paymentService";
    const CHECK_TRANSACTION = "https://backend.flexpay.cd/api/rest/v1/check";
// exemple : https://backend.flexpay.cd/api/rest/v1/check/{orderNumber} GET
    # PAYMENT METHODS
    const PAYMENT_METHOD_CARD = "VISA";
    const PAYMENT_METHOD_MOBILE = "MOBILE MONEY";

    const GENERIC_FAIL_MESSAGE = "Veuillez réessayer, si le problème persist veuillez contacter le support.";
}