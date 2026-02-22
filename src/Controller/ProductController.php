<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\RateRepository;
use App\Service\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_product_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private ApiResponse $apiResponse,
        private CategoryRepository $categoryRepository,
        private RateRepository $rateRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 15);
        $limit = min(max(1, $limit), 100);
        $offset = ($page - 1) * $limit;

        $criteria = ['isActive' => true];
        $categoryId = $request->query->get('category');
        if ($categoryId !== null && $categoryId !== '') {
            $category = $this->categoryRepository->find($categoryId);
            $criteria['category'] = $category;
        }
        $orderBy = ['createdAt' => 'DESC'];

        $products = $this->productRepository->findBy($criteria, $orderBy, $limit, $offset);
        $total = $this->productRepository->count($criteria);

        $user = $this->getUser();
        if(!$user){
            return $this->apiResponse->success(
                [
                    'items' => $products,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => (int) ceil($total / $limit),
                    ],
                ],
                'Liste des produits.',
                200,
                ['product:list']
            );
        }

        $preferedCurrency = $user->getCurrency();

        if(!$preferedCurrency || $preferedCurrency === 'USD'){
            return $this->apiResponse->success(
                [
                    'items' => $products,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => (int) ceil($total / $limit),
                    ],
                ],
                'Liste des produits.',
                200,
                ['product:list']
            );
        }

        $rate = $this->rateRepository->findOneBy([
            'currencyFrom' => 'USD',
            'currencyTo' => $preferedCurrency,
            'isActive' => true,
        ]);
        
        if(!$rate){
            return $this->apiResponse->success(
                [
                    'items' => $products,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => (int) ceil($total / $limit),
                    ],
                ],
                'Liste des produits.',
                200,
                ['product:list']
            );
        }

        $ratevalue = $rate->getRate();

       

        foreach ($products as $product) {
            $convertedPrice = round($product->getPrice() * $ratevalue, 5);
            $product->setPrice($convertedPrice);
            $product->setCurrency($preferedCurrency);
        }

        return $this->apiResponse->success(
            [
                'items' => $products,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => (int) ceil($total / $limit),
                ],
            ],
            'Liste des produits.',
            200,
            ['product:list']
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $product = $this->productRepository->findOneBy(['id' => $id, 'isActive' => true]);

        if (!$product) {
            return $this->apiResponse->error('Produit introuvable.', 404);
        }

        $user = $this->getUser();
        if(!$user){
            return $this->apiResponse->success(
            $product,
            'Detail du produit.',
            200,
            ['product:detail']
            );
        }
        $preferedCurrency = $user->getCurrency();
        if(!$preferedCurrency || $preferedCurrency === 'USD'){
            return $this->apiResponse->success(
                $product,
                'Detail du produit.',
                200,
                ['product:detail']
            );
        }


        $rate =$this->rateRepository->findOneBy([
            'currencyFrom' => 'USD',
            'currencyTo' => $preferedCurrency,
            'isActive' => true,
        ]);

        if(!$rate){
            return $this->apiResponse->success(
                $product,
                'Detail du produit.',
                200,
                ['product:detail']
            );
        }

        $ratevalue = $rate->getRate();
        $convertedPrice = round($product->getPrice() * $ratevalue, 5);
        $product->setPrice($convertedPrice);
        $product->setCurrency($preferedCurrency);

        return $this->apiResponse->success(
            $product,
            'Detail du produit.',
            200,
            ['product:detail']
        );
    }
}
