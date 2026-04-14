<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\RegisterInput;
use App\Service\JsonPayloadParser;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationService $registrationService,
        private readonly JsonPayloadParser $payloadParser,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $this->payloadParser->parse($request);
        $input = RegisterInput::fromRequestPayload($payload);

        $result = $this->registrationService->register($input);

        return new JsonResponse($result, Response::HTTP_CREATED);
    }
}
