<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Mail\MailProviderFactory;

final class ImapConnectionService
{
    public function __construct(
        private readonly MailProviderFactory $mailProviderFactory,
    ) {
    }

    public function testConnection(int $userId): bool
    {
        return $this->mailProviderFactory->createForUser($userId)->testConnection();
    }

    public function hasImapConfigured(int $userId): bool
    {
        return $this->mailProviderFactory->hasConfiguredMailbox($userId);
    }

    public function fetchEmailsSince(int $userId, ?\DateTimeImmutable $since): array
    {
        return $this->mailProviderFactory->createForUser($userId)->fetchEmailsSince($since);
    }

    public function fetchUnseenEmails(int $userId): array
    {
        return $this->fetchEmailsSince($userId, null);
    }

    /**
     * @return string[]
     */
    public function listAvailableFolders(int $userId): array
    {
        return $this->mailProviderFactory->createForUser($userId)->listAvailableFolders();
    }
}
