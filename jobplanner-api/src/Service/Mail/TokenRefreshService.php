<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\UserMailboxSettings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TokenRefreshService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $googleClientId = '',
        private readonly string $googleClientSecret = '',
        private readonly string $azureClientId = '',
        private readonly string $azureClientSecret = '',
    ) {
    }

    public function ensureValid(UserMailboxSettings $settings): void
    {
        if (!in_array($settings->getOauthProvider(), ['google', 'microsoft'], true) || !$settings->getRefreshToken()) {
            return;
        }

        $expiresAt = $settings->getTokenExpiresAt();
        $now = new \DateTimeImmutable('+60 seconds');

        if (null !== $expiresAt && $expiresAt > $now) {
            return;
        }

        $this->refreshToken($settings);
    }

    private function refreshToken(UserMailboxSettings $settings): void
    {
        $provider = $settings->getOauthProvider();
        $url = 'google' === $provider
            ? 'https://oauth2.googleapis.com/token'
            : 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

        $clientId = 'google' === $provider ? $this->googleClientId : $this->azureClientId;
        $clientSecret = 'google' === $provider ? $this->googleClientSecret : $this->azureClientSecret;

        try {
            $body = [
                'refresh_token' => $settings->getRefreshToken(),
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
            ];

            if ('microsoft' === $provider) {
                $body['scope'] = 'openid email profile offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send';
            }

            $response = $this->httpClient->request('POST', $url, [
                'body' => $body,
            ]);

            $tokenData = $response->toArray();

            $settings->setAccessToken((string) ($tokenData['access_token'] ?? ''));
            $settings->setTokenExpiresAt(new \DateTimeImmutable('+'.(int) ($tokenData['expires_in'] ?? 0).' seconds'));

            $this->entityManager->persist($settings);
            $this->entityManager->flush();

            $this->logger->info('OAuth token refreshed', ['provider' => $provider]);
        } catch (\Throwable $e) {
            $this->logger->error('OAuth token refresh failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
