<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiResponse;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/secure/users', name: 'api_user_')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private FileUploader $fileUploader
    ) {}

    private function format(User $user): array
    {
        return [
            'id'           => $user->getId(),
            'username'     => $user->getUsername(),
            'email'        => $user->getEmail(),
            'firstname'    => $user->getFirstname(),
            'lastname'     => $user->getLastname(),
            'phone'        => $user->getPhone(),
            'gender'       => $user->getGender(),
            'birthAt'      => $user->getBirthAt()?->format('Y-m-d'),
            'placeOfBirth' => $user->getPlaceOfBirth(),
            'picture'      => $user->getPicture(),
            'language'     => $user->getLanguage(),
            'currency'     => $user->getCurrency(),
            'roles'        => $user->getRoles(),
            'createdAt'    => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Créer un compte utilisateur (registration publique).
     * Body multipart ou JSON : username, password, email, firstname, lastname
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $username  = $data['username']  ?? null;
            $password  = $data['password']  ?? null;
            $email     = $data['email']     ?? null;
            $firstname = $data['firstname'] ?? null;
            $lastname  = $data['lastname']  ?? null;
            $phone    = $data['phone']     ?? null;
            $gender  = $data['gender']    ?? null;
            $birthAt  = $data['birthAt']   ?? null;
            $placeOfBirth = $data['placeOfBirth'] ?? null;
            $language = $data['language'] ?? null;
            $currency = $data['currency'] ?? null;
            $isAccepted = $data['isAccepted'] ?? null;

            if (!$username || !$password) {
                return $this->apiResponse->error('Les champs username et password sont requis.', 400);
            }

            $errors = [];

            if (strlen($username) < 3 || strlen($username) > 50) {
                $errors['username'][] = 'Le username doit contenir entre 3 et 50 caractères.';
            }
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                $errors['username'][] = 'Le username ne peut contenir que des lettres, chiffres, tirets, points et underscores.';
            }
            if (strlen($password) < 8) {
                $errors['password'][] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors['password'][] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors['password'][] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors['password'][] = 'Le mot de passe doit contenir au moins un chiffre.';
            }

            if (!empty($errors)) {
                return $this->apiResponse->error('Données invalides.', 422, $errors);
            }

            $existing = $this->userRepository->findOneBy(['username' => $username]);
            if ($existing) {
                return $this->apiResponse->error('Ce nom d\'utilisateur n\'est pas disponible.', 409);
            }

            $user = new User();
            $user->setUsername($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($email);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setPhone($phone);
            $user->setGender($gender);
            $user->setPlaceOfBirth($placeOfBirth);
            $user->setLanguage($language);
            $user->setCurrency($currency);
            $user->setRoles(User::ROLE_USER);
            $user->setIsAccepted((bool)$isAccepted);
            if ($birthAt) {
                $user->setBirthAt(new \DateTimeImmutable($birthAt));
            }

            $this->em->persist($user);
            $this->em->flush();

            return $this->apiResponse->success($this->format($user), 'Compte créé avec succès.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Détails de l'utilisateur connecté.
     */
    #[Route('/me', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            return $this->apiResponse->success($this->format($user), 'Profil utilisateur.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Mise à jour du profil de l'utilisateur connecté.
     * Accepte multipart/form-data (pour la photo) ou JSON.
     * Champs : firstname, lastname, email, phone, gender, birthAt, placeOfBirth, language, currency, picture (file), password
     */
    #[Route('/me', name: 'update', methods: ['POST', 'PUT', 'PATCH'])]
    public function update(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            // Supporte JSON et multipart/form-data
            $isJson = str_contains($request->headers->get('Content-Type', ''), 'application/json');
            $data   = $isJson ? json_decode($request->getContent(), true) : $request->request->all();

            if (isset($data['firstname']))    $user->setFirstname($data['firstname']);
            if (isset($data['lastname']))     $user->setLastname($data['lastname']);
            if (isset($data['email']))        $user->setEmail($data['email']);
            if (isset($data['phone']))        $user->setPhone($data['phone']);
            if (isset($data['gender']))       $user->setGender($data['gender']);
            if (isset($data['language']))     $user->setLanguage($data['language']);
            if (isset($data['currency']))     $user->setCurrency($data['currency']);
            if (isset($data['placeOfBirth'])) $user->setPlaceOfBirth($data['placeOfBirth']);

            if (!empty($data['birthAt'])) {
                $user->setBirthAt(new \DateTimeImmutable($data['birthAt']));
            }

            if (!empty($data['password'])) {
                $newPassword = $data['password'];
                $passwordErrors = [];

                if (strlen($newPassword) < 8) {
                    $passwordErrors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
                }
                if (!preg_match('/[A-Z]/', $newPassword)) {
                    $passwordErrors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
                }
                if (!preg_match('/[a-z]/', $newPassword)) {
                    $passwordErrors[] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
                }
                if (!preg_match('/[0-9]/', $newPassword)) {
                    $passwordErrors[] = 'Le mot de passe doit contenir au moins un chiffre.';
                }

                if (!empty($passwordErrors)) {
                    return $this->apiResponse->error('Mot de passe invalide.', 422, ['password' => $passwordErrors]);
                }

                $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
            }

            // Gestion de la photo de profil
            $pictureFile = $request->files->get('picture');
            if ($pictureFile) {
                if ($user->getPicture()) {
                    $this->fileUploader->delete($user->getPicture());
                }
                $pictureFileName = $this->fileUploader->uploadPublic($pictureFile, 'profil');
                $user->setPicture($pictureFileName);
            }

            $this->em->flush();

            return $this->apiResponse->success($this->format($user), 'Profil mis à jour avec succès.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Suppression (soft delete) du compte de l'utilisateur connecté.
     */
    #[Route('/me', name: 'delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $user->setIsDeleted(true);
            $this->em->flush();

            return $this->apiResponse->success(null, 'Compte supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
