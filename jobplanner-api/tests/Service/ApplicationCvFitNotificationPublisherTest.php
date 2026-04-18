<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\ApplicationCvFitNotificationPublisher;
use App\Service\MercureJwtFactory;
use App\Tests\Support\TestEntityHelpers;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApplicationCvFitNotificationPublisherTest extends TestCase
{
    use TestEntityHelpers;

    public function testPublishSendsMercureNotificationPayload(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(202);

        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://mercure.example/hub',
                $this->callback(function (array $options): bool {
                    $this->assertSame('application/x-www-form-urlencoded', $options['headers']['Content-Type']);
                    $this->assertArrayHasKey('Authorization', $options['headers']);
                    $this->assertStringStartsWith('Bearer ', $options['headers']['Authorization']);

                    $token = substr($options['headers']['Authorization'], 7);
                    [$headerPart, $payloadPart, $signaturePart] = explode('.', $token);

                    $payload = $this->decodeBase64UrlJson($payloadPart);
                    $this->assertSame(['urn:jobplanner:user:42:notifications'], $payload['mercure']['publish']);

                    $this->assertSame($this->expectedSignature($headerPart, $payloadPart, 'secret-key'), $signaturePart);

                    parse_str($options['body'], $body);
                    $this->assertSame('urn:jobplanner:user:42:notifications', $body['topic']);

                    $data = json_decode($body['data'], true);
                    $this->assertIsArray($data);
                    $this->assertSame('application.cv_fit.updated', $data['type']);
                    $this->assertSame(77, $data['data']['applicationId']);
                    $this->assertSame('completed', $data['data']['status']);

                    return true;
                })
            )
            ->willReturn($response);

        $publisher = new ApplicationCvFitNotificationPublisher(
            $httpClient,
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            'https://mercure.example/hub',
        );

        $application = $this->createApplication(77, 42);
        $application->setCvFitAnalysisStatus('completed');
        $application->setCvFitAnalysisResult(['score' => 92]);
        $application->setCvFitAnalysisRequestedAt(new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')));
        $application->setCvFitAnalysisCompletedAt(new \DateTimeImmutable('2026-04-15 10:05:00', new \DateTimeZone('UTC')));

        $publisher->publish($application);
    }

    private function createApplication(int $applicationId, int $ownerId): Application
    {
        $user = (new User())
            ->setEmail('candidate@example.com')
            ->setPassword('secret');
        $this->setEntityId($user, $ownerId);

        $jobOffer = (new JobOffer())
            ->setTitle('Backend Engineer')
            ->setCompany('Acme');

        $application = (new Application())
            ->setOwner($user)
            ->setJobOffer($jobOffer);
        $this->setEntityId($application, $applicationId);

        return $application;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBase64UrlJson(string $value): array
    {
        $decoded = json_decode(base64_decode(strtr($value, '-_', '+/'), true), true);

        self::assertIsArray($decoded);

        return $decoded;
    }

    private function expectedSignature(string $headerPart, string $payloadPart, string $secret): string
    {
        $signature = hash_hmac('sha256', $headerPart.'.'.$payloadPart, $secret, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
