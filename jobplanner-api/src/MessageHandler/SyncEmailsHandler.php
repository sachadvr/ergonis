<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncEmailsMessage;
use App\Repository\UserMailboxSettingsRepository;
use App\Repository\UserRepository;
use App\Service\EmailSyncProcessor;
use App\Service\ImapConnectionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncEmailsHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImapConnectionService $imapConnectionService,
        private EmailSyncProcessor $emailSyncProcessor,
        private UserMailboxSettingsRepository $mailboxSettingsRepository,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncEmailsMessage $message): void
    {
        $this->logger->info('Starting email sync', ['userId' => $message->getUserId()]);

        $userId = $message->getUserId();

        if (!$this->imapConnectionService->hasImapConfigured($userId)) {
            $this->logger->warning('IMAP not configured', ['userId' => $userId]);

            return;
        }

        $user = $this->userRepository->find($userId);
        $mailboxSettings = $this->mailboxSettingsRepository->findByUserId($userId);
        $syncSince = $mailboxSettings?->getLastSyncedAt() ?? $user?->getCreatedAt() ?? new \DateTimeImmutable('-1 day');

        $emails = $this->imapConnectionService->fetchEmailsSince($userId, $syncSince);
        $this->logger->debug('Fetched emails', ['count' => count($emails), 'userId' => $userId, 'since' => $syncSince->format(DATE_ATOM)]);

        $newSyncAt = $this->emailSyncProcessor->process($emails, $userId, $syncSince);

        if (null !== $mailboxSettings) {
            $mailboxSettings->setLastSyncedAt($newSyncAt);
            $this->entityManager->persist($mailboxSettings);
            $this->entityManager->flush();
        }

        $this->logger->info('Email sync completed', ['userId' => $userId, 'processed' => count($emails)]);
    }
}
