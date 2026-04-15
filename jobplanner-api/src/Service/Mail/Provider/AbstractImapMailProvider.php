<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\MailProviderInterface;
use Psr\Log\LoggerInterface;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Support\FolderCollection;

abstract class AbstractImapMailProvider implements MailProviderInterface
{
    private ?ClientManager $clientManager = null;

    public function __construct(
        protected readonly EmailMessageMapper $messageMapper,
        protected readonly LoggerInterface $logger,
        ?ClientManager $clientManager = null,
    ) {
        $this->clientManager = $clientManager;
    }

    public function testConnection(): bool
    {
        $client = $this->buildClient();
        if (null === $client) {
            return false;
        }

        try {
            if (!$client->isConnected()) {
                $client->connect();
            }

            return null !== $this->getMailboxFolder($client);
        } catch (\Throwable) {
            return false;
        }
    }

    public function fetchEmailsSince(?\DateTimeImmutable $since): array
    {
        $client = $this->buildClient();
        if (null === $client) {
            return [];
        }

        return $this->fetchFromClientSince($client, $since);
    }

    /**
     * @return string[]
     */
    public function listAvailableFolders(): array
    {
        $client = $this->buildClient();
        if (null === $client) {
            return [];
        }

        try {
            if (!$client->isConnected()) {
                $client->connect();
            }
        } catch (\Throwable) {
            return [];
        }

        return $this->listAvailableFolderNames($client);
    }

    abstract protected function buildClient(): ?Client;

    abstract protected function resolveFolderName(): ?string;

    protected function getDefaultFolderName(): string
    {
        return 'INBOX';
    }

    protected function getClientManager(): ClientManager
    {
        if (null === $this->clientManager) {
            $this->clientManager = new ClientManager();
        }

        return $this->clientManager;
    }

    protected function getMailboxFolder(Client $client): mixed
    {
        return $client->getFolder($this->getMailboxFolderName()) ?? null;
    }

    protected function getMailboxFolderName(): string
    {
        $resolvedFolderName = trim((string) $this->resolveFolderName());

        return '' !== $resolvedFolderName ? $resolvedFolderName : $this->getDefaultFolderName();
    }

    /**
     * @return string[]
     */
    protected function listAvailableFolderNames(Client $client): array
    {
        try {
            $folders = $client->getFolders();
        } catch (\Throwable) {
            return [];
        }

        if (!$folders instanceof FolderCollection) {
            return [];
        }

        $names = [];
        foreach ($folders as $folder) {
            if (is_object($folder) && isset($folder->full_name)) {
                $names[] = (string) $folder->full_name;
                continue;
            }

            if (is_object($folder) && isset($folder->name)) {
                $names[] = (string) $folder->name;
            }
        }

        return $names;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromClientSince(Client $client, ?\DateTimeImmutable $since): array
    {
        try {
            if (!$client->isConnected()) {
                $client->connect();
            }

            $folder = $this->getMailboxFolder($client);
            if (null === $folder) {
                $this->logger->warning('IMAP folder not found', [
                    'folder' => $this->getMailboxFolderName(),
                    'availableFolders' => $this->listAvailableFolderNames($client),
                ]);

                return [];
            }

            $query = $folder->messages();
            if (null !== $since) {
                $query = $query->since($since->format('d-M-Y'));
            } else {
                $query = $query->unseen();
            }

            $messages = $query->get();
            if (null === $messages) {
                return [];
            }

            $emails = [];
            foreach ($messages as $message) {
                $mapped = $this->messageMapper->map($message);
                if (null !== $since && !$this->isEmailAfterOrEqualTo((string) $mapped['date'], $since)) {
                    continue;
                }

                $emails[] = $mapped;
            }

            return $emails;
        } catch (\Throwable $e) {
            $this->logger->error('IMAP fetch failed', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return [];
        }
    }

    protected function isEmailAfterOrEqualTo(string $date, \DateTimeImmutable $since): bool
    {
        try {
            return new \DateTimeImmutable($date) >= $since;
        } catch (\Throwable) {
            return true;
        }
    }
}
