<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ConfigController extends AbstractController
{
    #[Route('/config', name: 'api_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        return new JsonResponse([]);
    }
}
