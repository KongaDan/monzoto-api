<?php

namespace App\Controller;

use App\Repository\PromoRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/promo', name: 'api_promo_')]
class PromoController extends AbstractController
{
    public function __construct(
        private PromoRepository $promoRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    /**
     * Vérifie un code promo et retourne ses détails s'il est valide et actif.
     */
    #[Route('/check', name: 'check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $code = $data['code'] ?? null;

            if (!$code) {
                return $this->apiResponse->error('Le champ code est requis.', 400);
            }

            $promo = $this->promoRepository->findActiveByCode($code);

            if (!$promo) {
                return $this->apiResponse->error('Code promo invalide ou expiré.', 404);
            }

            return $this->apiResponse->success(
                [
                    'code'           => $promo->getCode(),
                    'type'           => $promo->getType(),
                    'value'          => $promo->getValue(),
                    'currency'       => $promo->getCurrency(),
                    'minOrderAmount' => $promo->getMinOrderAmount(),
                    'maxOrderAmount' => $promo->getMaxOrderAmount(),
                    'usageLimit'     => $promo->getUsageLimit(),
                    'usageCount'     => $promo->getUsageCount(),
                    'validFrom'      => $promo->getValidFrom()?->format('Y-m-d H:i:s'),
                    'validTo'        => $promo->getValidTo()?->format('Y-m-d H:i:s'),
                ],
                'Code promo valide.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Applique un code promo et incrémente son compteur d'utilisation.
     */
    #[Route('/use', name: 'use', methods: ['POST'])]
    public function use(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $code = $data['code'] ?? null;

            if (!$code) {
                return $this->apiResponse->error('Le champ code est requis.', 400);
            }

            $promo = $this->promoRepository->findActiveByCode($code);

            if (!$promo) {
                return $this->apiResponse->error('Code promo invalide ou expiré.', 404);
            }

            $promo->setUsageCount($promo->getUsageCount() + 1);

            $this->em->flush();

            return $this->apiResponse->success(
                [
                    'code'       => $promo->getCode(),
                    'usageCount' => $promo->getUsageCount(),
                    'usageLimit' => $promo->getUsageLimit(),
                ],
                'Code promo appliqué avec succès.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
