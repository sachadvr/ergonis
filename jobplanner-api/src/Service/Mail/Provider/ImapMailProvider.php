<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Entity\UserMailboxSettings;
use App\Security\MailboxSecretEncryptor;
use App\Service\Mail\EmailMessageMapper;
use Psr\Log\LoggerInterface;
use Webklex\PHPIMAP\Client;

final class ImapMailProvider extends AbstractImapMailProvider
{
    public function __construct(
        private readonly ?UserMailboxSettings $settings,
        EmailMessageMapper $messageMapper,
        LoggerInterface $logger,
        private readonly MailboxSecretEncryptor $secretEncryptor,
        private readonly string $imapHost = '',
        private readonly string $imapPort = '993',
        private readonly string $imapUser = '',
        private readonly string $imapPassword = '',
        private readonly string $imapEncryption = 'ssl',
    ) {
        parent::__construct($messageMapper, $logger);
    }

    protected function resolveFolderName(): ?string
    {
        return $this->settings?->getImapFolder();
    }

    protected function buildClient(): ?Client
    {
        $host = $this->settings?->getImapHost() ?? $this->imapHost;
        $port = $this->settings?->getImapPort() ?? (int) $this->imapPort;
        $username = $this->settings?->getImapUser() ?? $this->imapUser;
        $password = $this->settings?->getImapPassword() ?? $this->imapPassword;
        $encryption = $this->settings?->getImapEncryption() ?? $this->imapEncryption;

        $password = $this->secretEncryptor->decrypt($password) ?? '';

        if ('' === $host || '' === $username || '' === $password) {
            return null;
        }

        try {
            $config = [
                'host' => $host,
                'port' => $port > 0 ? $port : 993,
                'encryption' => $this->resolveEncryption($host, $encryption),
                'validate_cert' => true,
                'username' => $username,
                'password' => $password,
                'protocol' => 'imap',
            ];

            $client = $this->getClientManager()->make($config);
            $client->connect();

            return $client;
        } catch (\Throwable $e) {
            error_log('IMAP client creation failed');

            return null;
        }
    }

    private function resolveEncryption(string $host, string $fallback): string
    {
        if (str_contains(strtolower($host), 'mailpit')) {
            return 'notls';
        }

        return '' !== $fallback ? $fallback : 'ssl';
    }
}
