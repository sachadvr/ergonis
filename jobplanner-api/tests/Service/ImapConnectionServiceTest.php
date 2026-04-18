<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\UserMailboxSettings;
use App\Security\MailboxSecretEncryptor;
use App\Service\ImapConnectionService;
use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\MailboxSettingsProviderInterface;
use App\Service\Mail\MailProviderFactory;
use App\Service\Mail\TokenRefreshService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ImapConnectionServiceTest extends TestCase
{
    public function testHasImapConfiguredReturnsFalseWhenNoSettingsExist(): void
    {
        $service = new ImapConnectionService($this->createFactory(null));

        $this->assertFalse($service->hasImapConfigured(1));
    }

    public function testHasImapConfiguredReturnsTrueWhenMailboxSettingsExist(): void
    {
        $service = new ImapConnectionService($this->createFactory($this->createMailpitSettings()));

        $this->assertTrue($service->hasImapConfigured(1));
    }

    public function testFetchUnseenEmailsUsesMailpitProvider(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options = []) {
                $response = $this->createStub(ResponseInterface::class);

                if (str_ends_with($url, '/api/v1/messages')) {
                    $response->method('toArray')->willReturn([
                        'messages' => [
                            ['id' => 'msg-1'],
                        ],
                    ]);
                } else {
                    $response->method('toArray')->willReturn([
                        'MessageID' => 'msg-1',
                        'From' => ['Address' => 'sender@example.com'],
                        'To' => [['Address' => 'candidate@example.com']],
                        'Subject' => 'Interview',
                        'Text' => 'Hello',
                        'HTML' => '',
                        'Date' => '2026-04-15 10:00:00',
                    ]);
                }

                return $response;
            });

        $service = new ImapConnectionService($this->createFactory($this->createMailpitSettings(), $httpClient));

        $emails = $service->fetchUnseenEmails(1);

        $this->assertCount(1, $emails);
        $this->assertSame('msg-1', $emails[0]['messageId']);
        $this->assertSame('sender@example.com', $emails[0]['fromAddress']);
    }

    private function createFactory(?UserMailboxSettings $settings, ?HttpClientInterface $httpClient = null): MailProviderFactory
    {
        $settingsProvider = new class($settings) implements MailboxSettingsProviderInterface {
            public function __construct(private readonly ?UserMailboxSettings $settings)
            {
            }

            public function findByUserId(int $userId): ?UserMailboxSettings
            {
                return 1 === $userId ? $this->settings : null;
            }
        };

        return new MailProviderFactory(
            $settingsProvider,
            new EmailMessageMapper(),
            new TokenRefreshService(
                $this->createStub(HttpClientInterface::class),
                $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
                $this->createStub(LoggerInterface::class),
                new MailboxSecretEncryptor('test-secret'),
            ),
            $httpClient ?? $this->createStub(HttpClientInterface::class),
            $this->createStub(LoggerInterface::class),
            new MailboxSecretEncryptor('test-secret'),
        );
    }

    private function createMailpitSettings(): UserMailboxSettings
    {
        return (new UserMailboxSettings())
            ->setImapHost('mailpit')
            ->setImapPort(1110)
            ->setImapUser('user@example.com')
            ->setImapPassword('secret');
    }
}
