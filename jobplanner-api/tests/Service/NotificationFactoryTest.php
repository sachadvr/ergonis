<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\NotificationItem;
use App\Entity\Application;
use App\Entity\ApplicationHistory;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Service\NotificationFactory;
use App\Tests\Support\TestEntityHelpers;
use PHPUnit\Framework\TestCase;

final class NotificationFactoryTest extends TestCase
{
    use TestEntityHelpers;

    public function testCreateFromRecruiterEmailMapsNotificationData(): void
    {
        $application = $this->createApplication(11, 'Backend Engineer');
        $recruiterEmail = (new RecruiterEmail())
            ->setApplication($application)
            ->setSender('recruiter@example.com')
            ->setSubject('Interview request')
            ->setReceivedAt(new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')))
            ->setIsSeen(true);
        $this->setEntityId($recruiterEmail, 22);

        $notification = (new NotificationFactory())->createFromRecruiterEmail($recruiterEmail);

        $this->assertNotificationItem(
            new NotificationItem(
                id: 22,
                type: 'email_received',
                title: 'New recruiter email',
                message: 'Interview request',
                createdAt: '2026-04-15T10:00:00+00:00',
                applicationId: 11,
                applicationTitle: 'Backend Engineer',
                sender: 'recruiter@example.com',
                subject: 'Interview request',
                isSeen: true,
                href: '/applications/11',
            ),
            $notification,
        );
    }

    public function testCreateFromApplicationHistoryUsesFallbackDescription(): void
    {
        $application = $this->createApplication(31, 'Platform Engineer');
        $history = (new ApplicationHistory())
            ->setApplication($application)
            ->setDescription(null)
            ->setIsSeen(false);
        $this->setEntityId($history, 44);
        $this->setDateTimeProperty($history, 'createdAt', new \DateTimeImmutable('2026-04-15 12:30:00', new \DateTimeZone('UTC')));

        $notification = (new NotificationFactory())->createFromApplicationHistory($history);

        $this->assertNotificationItem(
            new NotificationItem(
                id: 44,
                type: 'imported_from_extension',
                title: 'Application imported',
                message: 'Application created from the browser extension',
                createdAt: '2026-04-15T12:30:00+00:00',
                applicationId: 31,
                applicationTitle: 'Platform Engineer',
                sender: 'Browser extension',
                subject: 'Application created from the browser extension',
                isSeen: false,
                href: '/applications/31',
            ),
            $notification,
        );
    }

    public function testTopicForUserIdFormatsTopic(): void
    {
        $this->assertSame('urn:jobplanner:user:42:notifications', (new NotificationFactory())->topicForUserId(42));
    }

    private function createApplication(int $id, string $title): Application
    {
        $jobOffer = (new JobOffer())
            ->setTitle($title)
            ->setCompany('Acme');
        $this->setEntityId($jobOffer, $id + 100);

        $application = (new Application())->setJobOffer($jobOffer);
        $this->setEntityId($application, $id);

        return $application;
    }

    private function assertNotificationItem(NotificationItem $expected, NotificationItem $actual): void
    {
        $this->assertSame($expected->id, $actual->id);
        $this->assertSame($expected->type, $actual->type);
        $this->assertSame($expected->title, $actual->title);
        $this->assertSame($expected->message, $actual->message);
        $this->assertSame($expected->createdAt, $actual->createdAt);
        $this->assertSame($expected->applicationId, $actual->applicationId);
        $this->assertSame($expected->applicationTitle, $actual->applicationTitle);
        $this->assertSame($expected->sender, $actual->sender);
        $this->assertSame($expected->subject, $actual->subject);
        $this->assertSame($expected->isSeen, $actual->isSeen);
        $this->assertSame($expected->href, $actual->href);
    }

    private function setDateTimeProperty(object $entity, string $property, \DateTimeImmutable $value): void
    {
        $reflection = new \ReflectionProperty($entity, $property);
        $reflection->setValue($entity, $value);
    }
}
