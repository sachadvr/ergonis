<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Message\SyncEmailsMessage;
use App\Service\ImapConnectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class MailboxController extends AbstractController
{
    public function __construct(
        private readonly ImapConnectionService $imapService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/mailbox/test', name: 'api_mailbox_test', methods: ['POST'])]
    public function testConnection(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->imapService->hasImapConfigured($user->getId())) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune configuration IMAP'], Response::HTTP_BAD_REQUEST);
        }

        $ok = $this->imapService->testConnection($user->getId());

        return new JsonResponse([
            'success' => $ok,
            'message' => $ok ? 'Connexion réussie' : 'Échec de la connexion IMAP',
        ]);
    }

    #[Route('/mailbox/sync-now', name: 'api_mailbox_sync_now', methods: ['POST'])]
    public function syncNow(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->imapService->hasImapConfigured($user->getId())) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune configuration IMAP'], Response::HTTP_BAD_REQUEST);
        }

        $this->messageBus->dispatch(new SyncEmailsMessage($user->getId()));

        return new JsonResponse([
            'success' => true,
            'message' => 'Synchronisation lancée',
        ]);
    }
}
