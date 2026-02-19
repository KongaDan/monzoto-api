<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Service\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_review_')]
class ReviewController extends AbstractController
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private ProductRepository $productRepository,
        private ApiResponse $apiResponse,
        private EntityManagerInterface $em
    ) {}

    #[Route('/reviews/product/{productId}', name: 'by_product', methods: ['GET'])]
    public function byProduct(int $productId): JsonResponse
    {
        try {
            $product = $this->productRepository->find($productId);

            if (!$product) {
                return $this->apiResponse->error('Produit introuvable.', 404);
            }

            $reviews = $this->reviewRepository->findByProduct($productId, Review::STATUS_APPROVED);

            $data = array_map(fn($r) => [
                'id'        => $r->getId(),
                'rating'    => $r->getRating(),
                'title'     => $r->getTitle(),
                'comment'   => $r->getComment(),
                'createdAt' => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
                'customer'  => [
                    'id'        => $r->getCustomer()?->getId(),
                    'firstName' => $r->getCustomer()?->getFirstname(),
                    'lastName'  => $r->getCustomer()?->getLastname(),
                ],
            ], $reviews);

            return $this->apiResponse->success($data, 'Avis du produit.');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }

    /**
     * Ajouter un commentaire/avis sur un produit (utilisateur connecté).
     * Body JSON : productId, rating, title, comment
     */
    #[Route('/secure/reviews', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->apiResponse->error('Utilisateur non authentifié.', 401);
            }

            $data = json_decode($request->getContent(), true);

            $productId = $data['productId'] ?? null;
            $rating    = $data['rating'] ?? null;
            $title     = $data['title'] ?? null;
            $comment   = $data['comment'] ?? null;

            if (!$productId || !$rating) {
                return $this->apiResponse->error('Les champs productId et rating sont requis.', 400);
            }

            $errors = [];

            if (!is_int($rating) || $rating < 1 || $rating > 5) {
                $errors['rating'][] = 'La note doit être un entier entre 1 et 5.';
            }

            if (!empty($comment) && strlen($comment) > 1000) {
                $errors['comment'][] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
            }

            if (!empty($title) && strlen($title) > 255) {
                $errors['title'][] = 'Le titre ne doit pas dépasser 255 caractères.';
            }

            if (!empty($errors)) {
                return $this->apiResponse->error('Données invalides.', 422, $errors);
            }

            $product = $this->productRepository->find($productId);

            if (!$product) {
                return $this->apiResponse->error('Produit introuvable.', 404);
            }

            $review = new Review();
            $review->setProduct($product);
            $review->setCustomer($user);
            $review->setRating($rating);
            $review->setTitle($title);
            $review->setComment($comment);

            $this->em->persist($review);
            $this->em->flush();

            return $this->apiResponse->success([
                'id'        => $review->getId(),
                'rating'    => $review->getRating(),
                'title'     => $review->getTitle(),
                'comment'   => $review->getComment(),
                'status'    => $review->getStatus(),
                'createdAt' => $review->getCreatedAt()?->format('Y-m-d H:i:s'),
            ], 'Avis créé avec succès. Il sera approuvé par un modérateur.', 201);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Une erreur interne est survenue.', 500, $e->getMessage());
        }
    }
}
