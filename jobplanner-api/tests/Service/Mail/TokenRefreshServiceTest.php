<?php

declare(strict_types=1);

namespace App\Tests\Service\Mail;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\TokenRefreshService;
use App\Security\MailboxSecretEncryptor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class TokenRefreshServiceTest extends TestCase
{
    public function testEnsureValidDoesNotRefreshWhenTokenIsStillValid(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new TokenRefreshService($httpClient, $this->createStub(EntityManagerInterface::class), $this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'), 'gid', 'gsecret', 'aid', 'asecret');

        $settings = (new UserMailboxSettings())
            ->setOauthProvider('google')
            ->setRefreshToken('refresh')
            ->setTokenExpiresAt(new \DateTimeImmutable('+10 minutes'));

        $service->ensureValid($settings);
    }

    public function testEnsureValidRefreshesGoogleToken(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'access_token' => 'new-access-token',
            'expires_in' => 3600,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://oauth2.googleapis.com/token',
                $this->callback(function (array $options): bool {
                    $this->assertSame('refresh_token', $options['body']['grant_type']);
                    $this->assertSame('refresh-token', $options['body']['refresh_token']);
                    $this->assertSame('gid', $options['body']['client_id']);
                    $this->assertSame('gsecret', $options['body']['client_secret']);

                    return true;
                })
            )
            ->willReturn($response);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new TokenRefreshService($httpClient, $entityManager, $this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'), 'gid', 'gsecret', 'aid', 'asecret');

        $settings = (new UserMailboxSettings())
            ->setOauthProvider('google')
            ->setRefreshToken('refresh-token')
            ->setTokenExpiresAt(new \DateTimeImmutable('-10 minutes'));

        $service->ensureValid($settings);

        $this->assertSame('new-access-token', $settings->getAccessToken());
    }

    public function testEnsureValidRefreshesMicrosoftTokenWithScope(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'access_token' => 'azure-token',
            'expires_in' => 1800,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                $this->callback(function (array $options): bool {
                    $this->assertSame('refresh-token', $options['body']['refresh_token']);
                    $this->assertSame('aid', $options['body']['client_id']);
                    $this->assertSame('asecret', $options['body']['client_secret']);
                    $this->assertArrayHasKey('scope', $options['body']);

                    return true;
                })
            )
            ->willReturn($response);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new TokenRefreshService($httpClient, $entityManager, $this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'), 'gid', 'gsecret', 'aid', 'asecret');

        $settings = (new UserMailboxSettings())
            ->setOauthProvider('microsoft')
            ->setRefreshToken('refresh-token')
            ->setTokenExpiresAt(new \DateTimeImmutable('-10 minutes'));

        $service->ensureValid($settings);

        $this->assertSame('azure-token', $settings->getAccessToken());
    }

    public function testEnsureValidDoesNothingForNonOauthOrMissingRefreshToken(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new TokenRefreshService($httpClient, $this->createStub(EntityManagerInterface::class), $this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret'), 'gid', 'gsecret', 'aid', 'asecret');

        $plainSettings = (new UserMailboxSettings());
        $plainSettings->setSmtpHost('smtp.example.com');
        $service->ensureValid($plainSettings);

        $oauthNoRefresh = (new UserMailboxSettings())
            ->setOauthProvider('google')
            ->setTokenExpiresAt(new \DateTimeImmutable('-10 minutes'));

        $service->ensureValid($oauthNoRefresh);
    }

    public function testEnsureValidLogsErrorWhenRefreshFails(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'OAuth token refresh failed',
                $this->callback(static fn (array $context): bool => isset($context['provider'], $context['exception']))
            );

        $service = new TokenRefreshService($httpClient, $this->createStub(EntityManagerInterface::class), $logger, new MailboxSecretEncryptor('test-secret'), 'gid', 'gsecret', 'aid', 'asecret');

        $settings = (new UserMailboxSettings())
            ->setOauthProvider('google')
            ->setRefreshToken('refresh-token')
            ->setTokenExpiresAt(new \DateTimeImmutable('-10 minutes'));

        $service->ensureValid($settings);
    }
}
