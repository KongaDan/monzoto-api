<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\MunicipalityRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/addresses', name: 'api_address_')]
class AddressController extends AbstractController
{
    public function __construct(
        private AddressRepository $addressRepository,
        private MunicipalityRepository $municipalityRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    private function format(Address $address): array
    {
        return [
            'id'           => $address->getId(),
            'firstname'    => $address->getFirstname(),
            'lastname'     => $address->getLastname(),
            'phone'        => $address->getPhone(),
            'description'  => $address->getDescription(),
            'municipality' => [
                'id'   => $address->getMunicipality()?->getId(),
                'name' => $address->getMunicipality()?->getName(),
                'city' => [
                    'id'   => $address->getMunicipality()?->getCity()?->getId(),
                    'name' => $address->getMunicipality()?->getCity()?->getName(),
                    'shippingCost' => $address->getMunicipality()->getShippingCost(),
                    'currency' => $address->getMunicipality()->getCurrency(),
                    'isShippingAvailable' => $address->getMunicipality()->isShippingAvailable(),
                ],
            ],
        ];
    }

    /**
     * Liste toutes les adresses de livraison de l'utilisateur connecté.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $addresses = $this->addressRepository->findByUser($user->getId());

            return $this->apiResponse->success(
                array_map(fn($a) => $this->format($a), $addresses),
                'Adresses de livraison.'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Détails d'une adresse spécifique de l'utilisateur connecté.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $address = $this->addressRepository->find($id);

            if (!$address) {
                return $this->apiResponse->error('Adresse introuvable.', 404);
            }

            if ($address->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
            }

            return $this->apiResponse->success($this->format($address), 'Adresse de livraison.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Ajouter une adresse de livraison pour l'utilisateur connecté.
     * Body JSON : firstname, lastname, phone, municipalityId, description
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $data = json_decode($request->getContent(), true);

            $municipalityId = $data['municipalityId'] ?? null;

            if (!$municipalityId) {
                return $this->apiResponse->error('Le champ municipalityId est requis.', 400);
            }

            $municipality = $this->municipalityRepository->find($municipalityId);

            if (!$municipality || $municipality->isDeleted() || !$municipality->isActive()) {
                return $this->apiResponse->error('Commune introuvable.', 404);
            }

            $address = new Address();
            $address->setCustomer($user);
            $address->setFirstname($data['firstname'] ?? null);
            $address->setLastname($data['lastname'] ?? null);
            $address->setPhone($data['phone'] ?? null);
            $address->setDescription($data['description'] ?? null);
            $address->setMunicipality($municipality);

            $this->em->persist($address);
            $this->em->flush();

            return $this->apiResponse->success($this->format($address), 'Adresse créée avec succès.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Modifier une adresse de livraison de l'utilisateur connecté.
     * Body JSON : firstname, lastname, phone, municipalityId, description (tous optionnels)
     */
    #[Route('/{id}', name: 'update', methods: ['POST', 'PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $address = $this->addressRepository->find($id);

            if (!$address) {
                return $this->apiResponse->error('Adresse introuvable.', 404);
            }

            if ($address->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['firstname']))    $address->setFirstname($data['firstname']);
            if (isset($data['lastname']))     $address->setLastname($data['lastname']);
            if (isset($data['phone']))        $address->setPhone($data['phone']);
            if (isset($data['description']))  $address->setDescription($data['description']);

            if (isset($data['municipalityId'])) {
                $municipality = $this->municipalityRepository->find($data['municipalityId']);

                if (!$municipality || $municipality->isDeleted() || !$municipality->isActive()) {
                    return $this->apiResponse->error('Commune introuvable.', 404);
                }

                $address->setMunicipality($municipality);
            }

            $this->em->flush();

            return $this->apiResponse->success($this->format($address), 'Adresse mise à jour avec succès.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Supprimer une adresse de livraison de l'utilisateur connecté.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $address = $this->addressRepository->find($id);

            if (!$address) {
                return $this->apiResponse->error('Adresse introuvable.', 404);
            }

            if ($address->getCustomer()?->getId() !== $user->getId()) {
                return $this->apiResponse->error('Accès non autorisé.', 403);
            }

            $this->em->remove($address);
            $this->em->flush();

            return $this->apiResponse->success(null, 'Adresse supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
