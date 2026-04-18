<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationHistory;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Entity\User;
use App\Service\MercureJwtFactory;
use App\Service\MercureNotificationPublisher;
use App\Service\NotificationFactory;
use App\Tests\Support\TestEntityHelpers;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MercureNotificationPublisherTest extends TestCase
{
    use TestEntityHelpers;

    public function testPublishRecruiterEmailSendsNotification(): void
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
                    $this->assertStringStartsWith('Bearer ', $options['headers']['Authorization']);

                    parse_str($options['body'], $body);
                    $this->assertSame('urn:jobplanner:user:42:notifications', $body['topic']);
                    $data = json_decode($body['data'], true);
                    $this->assertSame('email_received', $data['type']);
                    $this->assertSame('New recruiter email', $data['data']['title']);

                    return true;
                })
            )
            ->willReturn($response);

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            'https://mercure.example/hub',
        );

        $publisher->publishRecruiterEmail($this->createRecruiterEmail(42));
    }

    public function testPublishApplicationHistorySendsNotification(): void
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
                    parse_str($options['body'], $body);
                    $data = json_decode($body['data'], true);
                    $this->assertSame('imported_from_extension', $data['type']);

                    return true;
                })
            )
            ->willReturn($response);

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            'https://mercure.example/hub',
        );

        $publisher->publishApplicationHistory($this->createHistory(42));
    }

    public function testPublishRecruiterEmailSkipsNonIncomingMessages(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            'https://mercure.example/hub',
        );

        $email = $this->createRecruiterEmail(42);
        $email->setDirection('OUTGOING');

        $publisher->publishRecruiterEmail($email);
    }

    public function testPublishRecruiterEmailSkipsWhenOwnerIsMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            'https://mercure.example/hub',
        );

        $email = $this->createRecruiterEmail(42);
        $email->getApplication()->setOwner(null);

        $publisher->publishRecruiterEmail($email);
    }

    public function testPublishApplicationHistoryLogsWarningsWhenHubFails(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to publish Mercure notification',
                $this->callback(static fn (array $context): bool => isset($context['historyId'], $context['error']))
            );

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $logger,
            'https://mercure.example/hub',
        );

        $publisher->publishApplicationHistory($this->createHistory(42));
    }

    public function testPublishRecruiterEmailLogsWhenHubRejectsNotification(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = new class implements ResponseInterface {
            public function getStatusCode(): int { return 500; }
            public function getHeaders(bool $throw = true): array { return []; }
            public function getContent(bool $throw = true): string { return ''; }
            public function toArray(bool $throw = true): array { return []; }
            public function cancel(): void { }
            public function getInfo(?string $type = null): mixed { return null; }
        };

        $httpClient->expects($this->once())->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('warning');

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $logger,
            'https://mercure.example/hub',
        );

        $publisher->publishRecruiterEmail($this->createRecruiterEmail(42));
    }

    public function testPublishApplicationHistorySkipsWhenHubUrlMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $publisher = new MercureNotificationPublisher(
            $httpClient,
            new NotificationFactory(),
            new MercureJwtFactory('secret-key'),
            $this->createStub(LoggerInterface::class),
            '',
        );

        $publisher->publishApplicationHistory($this->createHistory(42));
    }

    private function createRecruiterEmail(int $ownerId): RecruiterEmail
    {
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->setEntityId($user, $ownerId);

        $jobOffer = (new JobOffer())->setTitle('Backend Engineer')->setCompany('Acme');
        $this->setEntityId($jobOffer, 10);

        $application = (new Application())->setOwner($user)->setJobOffer($jobOffer);
        $this->setEntityId($application, 11);

        return (new RecruiterEmail())
            ->setApplication($application)
            ->setSender('recruiter@example.com')
            ->setSubject('Interview')
            ->setBody('Hello')
            ->setMessageId('msg-1')
            ->setReceivedAt(new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')))
            ->setIsSeen(false)
            ->setDirection('INCOMING');
    }

    private function createHistory(int $ownerId): ApplicationHistory
    {
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->setEntityId($user, $ownerId);

        $jobOffer = (new JobOffer())->setTitle('Backend Engineer')->setCompany('Acme');
        $this->setEntityId($jobOffer, 10);

        $application = (new Application())->setOwner($user)->setJobOffer($jobOffer);
        $this->setEntityId($application, 11);

        $history = (new ApplicationHistory())
            ->setApplication($application)
            ->setDescription(null)
            ->setIsSeen(false);
        $this->setEntityId($history, 12);

        return $history;
    }
}
