<?php

namespace App\Controller;

use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cities', name: 'api_city_')]
class CityController extends AbstractController
{
    public function __construct(
        private CityRepository $cityRepository,
        private CountryRepository $countryRepository,
        private ApiResponse $apiResponse
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $cities = $this->cityRepository->findAllActive();

            $data = array_map(fn($c) => [
                'id'      => $c->getId(),
                'name'    => $c->getName(),
                'country' => [
                    'id'      => $c->getCountry()?->getId(),
                    'name'    => $c->getCountry()?->getName(),
                    'isoCode' => $c->getCountry()?->getIsoCode(),
                ],
            ], $cities);

            return $this->apiResponse->success($data, 'Liste des villes.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $city = $this->cityRepository->find($id);

            if (!$city || $city->isDeleted() || !$city->isActive()) {
                return $this->apiResponse->error('Ville introuvable.', 404);
            }

            return $this->apiResponse->success([
                'id'      => $city->getId(),
                'name'    => $city->getName(),
                'country' => [
                    'id'      => $city->getCountry()?->getId(),
                    'name'    => $city->getCountry()?->getName(),
                    'isoCode' => $city->getCountry()?->getIsoCode(),
                ],
            ], 'Ville trouvée.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    #[Route('/by-country/{countryId}', name: 'by_country', methods: ['GET'])]
    public function byCountry(int $countryId): JsonResponse
    {
        try {
            $country = $this->countryRepository->find($countryId);

            if (!$country || $country->isDeleted() || !$country->isActive()) {
                return $this->apiResponse->error('Pays introuvable.', 404);
            }

            $cities = $this->cityRepository->findByCountry($countryId);

            $data = array_map(fn($c) => [
                'id'   => $c->getId(),
                'name' => $c->getName(),
            ], $cities);

            return $this->apiResponse->success($data, 'Villes du pays.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
