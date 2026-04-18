<?php

declare(strict_types=1);

namespace App\Tests\Service\Mail;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\MailboxSettingsProviderInterface;
use App\Service\Mail\MailProviderFactory;
use App\Service\Mail\Provider\GoogleOAuthMailProvider;
use App\Service\Mail\Provider\ImapMailProvider;
use App\Service\Mail\Provider\MailpitMailProvider;
use App\Service\Mail\Provider\MicrosoftOAuthMailProvider;
use App\Service\Mail\TokenRefreshService;
use App\Security\MailboxSecretEncryptor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MailProviderFactoryTest extends TestCase
{
    public function testCreateForUserReturnsExpectedProviderTypes(): void
    {
        $googleSettings = new UserMailboxSettings();
        $googleSettings->setOauthProvider('google');
        $googleSettings->setImapUser('user@example.com');
        $googleSettings->setAccessToken('token');

        $microsoftSettings = new UserMailboxSettings();
        $microsoftSettings->setOauthProvider('microsoft');
        $microsoftSettings->setImapUser('user@example.com');
        $microsoftSettings->setAccessToken('token');

        $mailpitSettings = new UserMailboxSettings();
        $mailpitSettings->setImapHost('mailpit');
        $mailpitSettings->setImapPort(1110);
        $mailpitSettings->setImapUser('user@example.com');
        $mailpitSettings->setImapPassword('secret');

        $imapSettings = new UserMailboxSettings();
        $imapSettings->setImapHost('imap.example.com');
        $imapSettings->setImapUser('user@example.com');
        $imapSettings->setImapPassword('secret');

        $settingsProvider = new class($googleSettings, $microsoftSettings, $mailpitSettings, $imapSettings) implements MailboxSettingsProviderInterface {
            public function __construct(
                private readonly UserMailboxSettings $googleSettings,
                private readonly UserMailboxSettings $microsoftSettings,
                private readonly UserMailboxSettings $mailpitSettings,
                private readonly UserMailboxSettings $imapSettings,
            ) {
            }

            public function findByUserId(int $userId): ?UserMailboxSettings
            {
                return match ($userId) {
                    1 => $this->googleSettings,
                    2 => $this->microsoftSettings,
                    3 => $this->mailpitSettings,
                    4 => $this->imapSettings,
                    default => null,
                };
            }
        };

        $factory = new MailProviderFactory(
            $settingsProvider,
            new EmailMessageMapper(),
            new TokenRefreshService(
                $this->createStub(HttpClientInterface::class),
                $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
                $this->createStub(LoggerInterface::class),
                new MailboxSecretEncryptor('test-secret'),
            ),
            $this->createStub(HttpClientInterface::class),
            $this->createStub(LoggerInterface::class),
            new MailboxSecretEncryptor('test-secret'),
        );

        $this->assertInstanceOf(GoogleOAuthMailProvider::class, $factory->createForUser(1));
        $this->assertInstanceOf(MicrosoftOAuthMailProvider::class, $factory->createForUser(2));
        $this->assertInstanceOf(MailpitMailProvider::class, $factory->createForUser(3));
        $this->assertInstanceOf(ImapMailProvider::class, $factory->createForUser(4));
    }
}
