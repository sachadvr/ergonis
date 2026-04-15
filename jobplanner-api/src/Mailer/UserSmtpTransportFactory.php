<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Entity\UserMailboxSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final readonly class UserSmtpTransportFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function createTransport(UserMailboxSettings $settings): TransportInterface
    {
        $host = $settings->getSmtpHost();
        $port = $settings->getSmtpPort();
        $encryption = $settings->getSmtpEncryption();
        $username = $settings->getSmtpUser();
        $password = $settings->getSmtpPassword();

        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => 'smtp',
        };

        if (null !== $settings->getOauthProvider() && null !== $settings->getAccessToken()) {
            $oauthString = sprintf(
                "user=%s\1auth=Bearer %s\1\1",
                $settings->getImapUser(),
                $settings->getAccessToken()
            );
            $password = $oauthString;
            $username = $settings->getImapUser();
        }

        $dsn = sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            rawurlencode($username),
            rawurlencode($password),
            $host,
            $port
        );

        $transport = Transport::fromDsn($dsn, null, null, $this->logger);

        $this->logger->debug('Created user SMTP transport', [
            'host' => $host,
            'port' => $port,
            'scheme' => $scheme,
        ]);

        return $transport;
    }
}
