<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponse
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    public function success(
        $data = null,
        string $message = '',
        int $status = 200,
        array $groups = []
    ): JsonResponse {

        $json = $this->serializer->serialize(
            $data,
            'json',
            ['groups' => $groups]
        );

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => json_decode($json, true)
        ], $status);
    }

    public function error(
        string $message = 'Une erreur est survenue',
        int $status = 500,
        mixed $errors = null
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}

