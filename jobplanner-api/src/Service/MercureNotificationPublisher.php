<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RecruiterEmail;
use App\Entity\ApplicationHistory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class MercureNotificationPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private NotificationFactory $notificationFactory,
        private MercureJwtFactory $mercureJwtFactory,
        private LoggerInterface $logger,
        #[Autowire('%env(default:default_mercure_public_url:MERCURE_PUBLIC_URL)%')]
        private string $mercurePublicUrl,
    ) {
    }

    public function publishRecruiterEmail(RecruiterEmail $recruiterEmail): void
    {
        if ('INCOMING' !== $recruiterEmail->getDirection()) {
            return;
        }

        $owner = $recruiterEmail->getApplication()->getOwner();
        $ownerId = $owner?->getId();

        if (null === $ownerId || '' === $this->mercurePublicUrl) {
            return;
        }

        $notification = $this->notificationFactory->createFromRecruiterEmail($recruiterEmail);
        $topic = $this->notificationFactory->topicForUserId((int) $ownerId);
        $body = http_build_query([
            'topic' => $topic,
            'data' => json_encode([
                'type' => 'email_received',
                'data' => $notification,
            ], JSON_THROW_ON_ERROR),
        ]);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $headers['Authorization'] = 'Bearer '.$this->mercureJwtFactory->createToken(publishTopics: [$topic]);

        try {
            $response = $this->httpClient->request('POST', $this->mercurePublicUrl, [
                'headers' => $headers,
                'body' => $body,
            ]);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Mercure hub rejected notification publish', [
                    'emailId' => $recruiterEmail->getId(),
                    'statusCode' => $response->getStatusCode(),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to publish Mercure notification', [
                'emailId' => $recruiterEmail->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function publishApplicationHistory(ApplicationHistory $history): void
    {
        $owner = $history->getApplication()->getOwner();
        $ownerId = $owner?->getId();

        if (null === $ownerId || '' === $this->mercurePublicUrl) {
            return;
        }

        $notification = $this->notificationFactory->createFromApplicationHistory($history);
        $topic = $this->notificationFactory->topicForUserId((int) $ownerId);
        $body = http_build_query([
            'topic' => $topic,
            'data' => json_encode([
                'type' => 'imported_from_extension',
                'data' => $notification,
            ], JSON_THROW_ON_ERROR),
        ]);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $headers['Authorization'] = 'Bearer '.$this->mercureJwtFactory->createToken(publishTopics: [$topic]);

        try {
            $response = $this->httpClient->request('POST', $this->mercurePublicUrl, [
                'headers' => $headers,
                'body' => $body,
            ]);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Mercure hub rejected notification publish', [
                    'historyId' => $history->getId(),
                    'statusCode' => $response->getStatusCode(),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to publish Mercure notification', [
                'historyId' => $history->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

}
