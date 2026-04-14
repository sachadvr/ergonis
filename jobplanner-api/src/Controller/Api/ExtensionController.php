<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\JobOfferFromExtensionInput;
use App\Entity\User;
use App\Message\ProcessJobOfferFromExtensionMessage;
use App\Service\JsonPayloadParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ExtensionController extends AbstractController
{
    public function __construct(
        private readonly JsonPayloadParser $payloadParser,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/job_offers/from_extension', name: 'api_job_offers_from_extension', methods: ['POST'])]
    public function fromExtension(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $this->payloadParser->parse($request);
        $input = JobOfferFromExtensionInput::fromRequestPayload($payload);

        $this->messageBus->dispatch(new ProcessJobOfferFromExtensionMessage($user->getId(), $input));

        return new JsonResponse([
            'status' => 'queued',
        ], Response::HTTP_ACCEPTED);
    }
}
