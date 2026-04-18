<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\TokenRefreshService;
use App\Security\MailboxSecretEncryptor;
use Psr\Log\LoggerInterface;
use Webklex\PHPIMAP\Client;

abstract class AbstractOAuthMailProvider extends AbstractImapMailProvider
{
    public function __construct(
        protected readonly UserMailboxSettings $settings,
        protected readonly TokenRefreshService $tokenRefreshService,
        EmailMessageMapper $messageMapper,
        LoggerInterface $logger,
        protected readonly MailboxSecretEncryptor $secretEncryptor,
    ) {
        parent::__construct($messageMapper, $logger);
    }

    public function testConnection(): bool
    {
        $this->tokenRefreshService->ensureValid($this->settings);

        return parent::testConnection();
    }

    public function fetchEmailsSince(?\DateTimeImmutable $since): array
    {
        $this->tokenRefreshService->ensureValid($this->settings);

        return parent::fetchEmailsSince($since);
    }

    public function listAvailableFolders(): array
    {
        $this->tokenRefreshService->ensureValid($this->settings);

        return parent::listAvailableFolders();
    }

    protected function resolveFolderName(): ?string
    {
        return $this->settings->getImapFolder();
    }

    protected function buildClient(): ?Client
    {
        $host = $this->resolveHost();
        $username = trim($this->settings->getImapUser());
        $accessToken = trim((string) ($this->secretEncryptor->decrypt($this->settings->getAccessToken()) ?? ''));

        if ('' === $host || '' === $username || '' === $accessToken) {
            return null;
        }

        try {
            $config = [
                'host' => $host,
                'port' => 993,
                'encryption' => 'ssl',
                'validate_cert' => true,
                'username' => $username,
                'password' => $accessToken,
                'protocol' => 'imap',
                'authentication' => 'oauth',
                'options' => [
                    'DISABLE_AUTHENTICATOR' => ['GSSAPI', 'NTLM'],
                ],
            ];

            $client = $this->getClientManager()->make($config);
            $client->connect();

            return $client;
        } catch (\Throwable $e) {
            error_log('IMAP OAuth client creation failed');

            return null;
        }
    }

    abstract protected function resolveHost(): string;
}
