<?php

namespace App\Controller\admin;

use App\Repository\UserRepository;
use App\Service\ApiResponse;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users', name: 'api_admin_user_')]
class UserController extends AbstractController
{
    private string $privateApiKey;

    public function __construct(
        string $privateApiKey,
        private UserRepository $userRepository,
        private ApiResponse $apiResponse,
        private FileUploader $fileUploader,
        private EntityManagerInterface $em
    ) {
        $this->privateApiKey = $privateApiKey;
    }

    private function denyUnlessAuthorized(Request $request): ?JsonResponse
    {
        $header = $request->headers->get('Authorization');

        if (!$header) {
            return $this->apiResponse->error('Clé API manquante.', 401);
        }

        $token = trim($header);

        if ($token === '' || $this->privateApiKey === '' || !hash_equals($this->privateApiKey, $token)) {
            return $this->apiResponse->error('Clé API invalide.', 403);
        }

        return null;
    }

    /**
     * Modifie la photo de profil d'un utilisateur.
     * Header : Authorization: <private_api_key>
     * Body multipart/form-data : picture (file)
     */
    #[Route('/{userId}/picture', name: 'update_picture', methods: ['POST'])]
    public function updatePicture(int $userId, Request $request): JsonResponse
    {
        try {
            $denied = $this->denyUnlessAuthorized($request);
            if ($denied) {
                return $denied;
            }

            $user = $this->userRepository->find($userId);

            if (!$user || $user->isDeleted()) {
                return $this->apiResponse->error('Utilisateur introuvable.', 404);
            }

            $pictureFile = $request->files->get('picture');

            if (!$pictureFile) {
                return $this->apiResponse->error('Le fichier picture est requis.', 400);
            }

            if ($user->getPicture()) {
                $this->fileUploader->delete($user->getPicture());
            }

            $pictureFileName = $this->fileUploader->uploadPublic($pictureFile, 'profil');
            $user->setPicture($pictureFileName);

            $this->em->flush();

            return $this->apiResponse->success([
                'userId'  => $user->getId(),
                'picture' => $pictureFileName,
            ], 'Photo de profil mise à jour avec succès.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}

