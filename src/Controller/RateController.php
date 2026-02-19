<?php

namespace App\Controller;

use App\Repository\RateRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rates', name: 'api_rate_')]
class RateController extends AbstractController
{
    public function __construct(
        private RateRepository $rateRepository,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/convert', name: 'convert', methods: ['POST'])]
    public function getRate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $currencyFrom = $data['currencyFrom'] ?? null;
            $currencyTo   = $data['currencyTo'] ?? null;

            if (!$currencyFrom || !$currencyTo) {
                return $this->apiResponse->error(
                    'Les champs currencyFrom et currencyTo sont requis.',
                    400
                );
            }

            $rate = $this->rateRepository->findActiveRate($currencyFrom, $currencyTo);

            if (!$rate) {
                return $this->apiResponse->error(
                    'Aucun taux actif trouvé pour cette paire de devises.',
                    404
                );
            }

            return $this->apiResponse->success(
                [
                    'currencyFrom' => $rate->getCurrencyFrom(),
                    'currencyTo'   => $rate->getCurrencyTo(),
                    'rate'         => $rate->getRate(),
                    'validAt'      => $rate->getValidAt()?->format('Y-m-d H:i:s'),
                    'expiredAt'    => $rate->getExpiredAt()?->format('Y-m-d H:i:s'),
                ],
                'Taux de change récupéré avec succès.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error(
                'Une erreur interne est survenue.',
                500,
                $e->getMessage()
            );
        }
    }
}
