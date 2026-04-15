<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\Provider\GoogleOAuthMailProvider;
use App\Service\Mail\Provider\ImapMailProvider;
use App\Service\Mail\Provider\MailpitMailProvider;
use App\Service\Mail\Provider\MicrosoftOAuthMailProvider;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MailProviderFactory
{
    public function __construct(
        private readonly MailboxSettingsProviderInterface $mailboxSettingsProvider,
        private readonly EmailMessageMapper $messageMapper,
        private readonly TokenRefreshService $tokenRefreshService,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $imapHost = '',
        private readonly string $imapPort = '993',
        private readonly string $imapUser = '',
        private readonly string $imapPassword = '',
        private readonly string $imapEncryption = 'ssl',
        private readonly string $mailpitUrl = 'http://localhost:8025',
    ) {
    }

    public function hasConfiguredMailbox(int $userId): bool
    {
        $settings = $this->mailboxSettingsProvider->findByUserId($userId);
        if (null !== $settings) {
            return true;
        }

        return '' !== $this->imapHost && '' !== $this->imapUser && '' !== $this->imapPassword;
    }

    public function createForUser(int $userId): MailProviderInterface
    {
        $settings = $this->mailboxSettingsProvider->findByUserId($userId);

        if ($settings instanceof UserMailboxSettings) {
            return $this->createForSettings($settings);
        }

        if ($this->isMailpitHost($this->imapHost, (int) $this->imapPort)) {
            return $this->createMailpitProvider();
        }

        return new ImapMailProvider(
            null,
            $this->messageMapper,
            $this->logger,
            $this->imapHost,
            $this->imapPort,
            $this->imapUser,
            $this->imapPassword,
            $this->imapEncryption,
        );
    }

    private function createForSettings(UserMailboxSettings $settings): MailProviderInterface
    {
        return match ($settings->getOauthProvider()) {
            'google' => new GoogleOAuthMailProvider(
                $settings,
                $this->tokenRefreshService,
                $this->messageMapper,
                $this->logger,
            ),
            'microsoft' => new MicrosoftOAuthMailProvider(
                $settings,
                $this->tokenRefreshService,
                $this->messageMapper,
                $this->logger,
            ),
            default => $this->isMailpitHost($settings->getImapHost(), $settings->getImapPort())
                ? $this->createMailpitProvider()
                : new ImapMailProvider(
                    $settings,
                    $this->messageMapper,
                    $this->logger,
                ),
        };
    }

    private function createMailpitProvider(): MailProviderInterface
    {
        return new MailpitMailProvider(
            $this->httpClient,
            $this->messageMapper,
            $this->logger,
            $this->mailpitUrl,
        );
    }

    private function isMailpitHost(string $host, int $port): bool
    {
        return 1110 === $port || str_contains(strtolower($host), 'mailpit');
    }
}
