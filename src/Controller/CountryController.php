<?php

namespace App\Controller;

use App\Repository\CountryRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/countries', name: 'api_country_')]
class CountryController extends AbstractController
{
    public function __construct(
        private CountryRepository $countryRepository,
        private ApiResponse $apiResponse
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $countries = $this->countryRepository->findAllActive();

            $data = array_map(fn($c) => [
                'id'      => $c->getId(),
                'name'    => $c->getName(),
                'isoCode' => $c->getIsoCode(),
            ], $countries);

            return $this->apiResponse->success($data, 'Liste des pays.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $country = $this->countryRepository->find($id);

            if (!$country || $country->isDeleted() || !$country->isActive()) {
                return $this->apiResponse->error('Pays introuvable.', 404);
            }

            return $this->apiResponse->success([
                'id'      => $country->getId(),
                'name'    => $country->getName(),
                'isoCode' => $country->getIsoCode(),
            ], 'Pays trouvé.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
