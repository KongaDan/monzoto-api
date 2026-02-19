<?php

namespace App\Controller;

use App\Repository\CityRepository;
use App\Repository\MunicipalityRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/municipalities', name: 'api_municipality_')]
class MunicipalityController extends AbstractController
{
    public function __construct(
        private MunicipalityRepository $municipalityRepository,
        private CityRepository $cityRepository,
        private ApiResponse $apiResponse
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $municipalities = $this->municipalityRepository->findAllActive();

            $data = array_map(fn($m) => [
                'id'                 => $m->getId(),
                'name'               => $m->getName(),
                'shippingCost'       => $m->getShippingCost(),
                'currency'           => $m->getCurrency(),
                'isShippingAvailable'=> $m->isShippingAvailable(),
                'city'               => [
                    'id'   => $m->getCity()?->getId(),
                    'name' => $m->getCity()?->getName(),
                ],
            ], $municipalities);

            return $this->apiResponse->success($data, 'Liste des communes.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $municipality = $this->municipalityRepository->find($id);

            if (!$municipality || $municipality->isDeleted() || !$municipality->isActive()) {
                return $this->apiResponse->error('Commune introuvable.', 404);
            }

            return $this->apiResponse->success([
                'id'                 => $municipality->getId(),
                'name'               => $municipality->getName(),
                'shippingCost'       => $municipality->getShippingCost(),
                'currency'           => $municipality->getCurrency(),
                'isShippingAvailable'=> $municipality->isShippingAvailable(),
                'city'               => [
                    'id'   => $municipality->getCity()?->getId(),
                    'name' => $municipality->getCity()?->getName(),
                ],
            ], 'Commune trouvée.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    #[Route('/by-city/{cityId}', name: 'by_city', methods: ['GET'])]
    public function byCity(int $cityId): JsonResponse
    {
        try {
            $city = $this->cityRepository->find($cityId);

            if (!$city || $city->isDeleted() || !$city->isActive()) {
                return $this->apiResponse->error('Ville introuvable.', 404);
            }

            $municipalities = $this->municipalityRepository->findByCity($cityId);

            $data = array_map(fn($m) => [
                'id'                  => $m->getId(),
                'name'                => $m->getName(),
                'shippingCost'        => $m->getShippingCost(),
                'currency'            => $m->getCurrency(),
                'isShippingAvailable' => $m->isShippingAvailable(),
            ], $municipalities);

            return $this->apiResponse->success($data, 'Communes de la ville.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
