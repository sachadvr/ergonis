<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ConfigController extends AbstractController
{
    public function __construct(
        private readonly string $simulationMode = 'true',
    ) {
    }

    #[Route('/config', name: 'api_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        return new JsonResponse([
            'simulationMode' => filter_var($this->simulationMode, \FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
