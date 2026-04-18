<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncAllMailboxesMessage;
use App\Message\SyncEmailsMessage;
use App\Repository\UserMailboxSettingsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class SyncAllMailboxesHandler
{
    public function __construct(
        private UserMailboxSettingsRepository $mailboxSettingsRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncAllMailboxesMessage $message): void
    {
        foreach ($this->mailboxSettingsRepository->findActiveForSync() as $settings) {
            $user = $settings->getOwner();
            if (null === $user) {
                continue;
            }

            $this->logger->info('Starting mailbox sync for user {userId} ({email}) with provider {provider}', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'provider' => $settings->getOauthProvider(),
            ]);

            try {
                $this->messageBus->dispatch(new SyncEmailsMessage($user->getId()));
                $this->logger->info('Mailbox synced', [
                    'userId' => $user->getId(),
                    'email' => $user->getEmail(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Mailbox sync failed', [
                    'userId' => $user->getId(),
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
