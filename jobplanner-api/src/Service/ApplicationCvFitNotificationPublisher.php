<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ApplicationCvFitNotificationPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private MercureJwtFactory $mercureJwtFactory,
        private LoggerInterface $logger,
        #[Autowire('%env(string:MERCURE_PUBLIC_URL)%')]
        private string $mercurePublicUrl,
    ) {
    }

    public function publish(Application $application): void
    {
        $ownerId = $application->getOwner()?->getId();
        if (null === $ownerId || '' === $this->mercurePublicUrl) {
            return;
        }

        $topic = $this->topicForUserId((int) $ownerId);
        $payload = json_encode([
            'type' => 'application.cv_fit.updated',
            'data' => [
                'applicationId' => $application->getId(),
                'status' => $application->getCvFitAnalysisStatus(),
                'result' => $application->getCvFitAnalysisResult(),
                'requestedAt' => $application->getCvFitAnalysisRequestedAt()?->format(DATE_ATOM),
                'completedAt' => $application->getCvFitAnalysisCompletedAt()?->format(DATE_ATOM),
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $response = $this->httpClient->request('POST', $this->mercurePublicUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Bearer '.$this->mercureJwtFactory->createToken(publishTopics: [$topic]),
                ],
                'body' => http_build_query([
                    'topic' => $topic,
                    'data' => $payload,
                ]),
            ]);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Mercure hub rejected CV fit notification', [
                    'applicationId' => $application->getId(),
                    'statusCode' => $response->getStatusCode(),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to publish CV fit notification', [
                'applicationId' => $application->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function topicForUserId(int $userId): string
    {
        return 'urn:jobplanner:user:'.$userId.':notifications';
    }
}
