<?php

declare(strict_types=1);

namespace App\Tests\Mailer;

use App\Entity\UserMailboxSettings;
use App\Mailer\UserSmtpTransportFactory;
use App\Security\MailboxSecretEncryptor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class UserSmtpTransportFactoryTest extends TestCase
{
    public function testCreateTransportBuildsStandardSmtpTransport(): void
    {
        $factory = new UserSmtpTransportFactory($this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'));

        $transport = $factory->createTransport(
            (new UserMailboxSettings())
                ->setSmtpHost('smtp.example.com')
                ->setSmtpPort(587)
                ->setSmtpEncryption('tls')
                ->setSmtpUser('user@example.com')
                ->setSmtpPassword('secret')
        );

        $this->assertInstanceOf(TransportInterface::class, $transport);
    }

    public function testCreateTransportBuildsOauthTransport(): void
    {
        $factory = new UserSmtpTransportFactory($this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'));

        $transport = $factory->createTransport(
            (new UserMailboxSettings())
                ->setOauthProvider('google')
                ->setImapUser('user@example.com')
                ->setAccessToken('access-token')
                ->setSmtpHost('smtp.gmail.com')
                ->setSmtpPort(465)
                ->setSmtpEncryption('ssl')
                ->setSmtpUser('user@example.com')
        );

        $this->assertInstanceOf(TransportInterface::class, $transport);
    }
}
