<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\NotificationItem;
use App\Entity\ApplicationHistory;
use App\Entity\RecruiterEmail;

final readonly class NotificationFactory
{
    private const TOPIC_PREFIX = 'urn:jobplanner:user:';

    public function createFromRecruiterEmail(RecruiterEmail $recruiterEmail): NotificationItem
    {
        $application = $recruiterEmail->getApplication();
        $applicationId = (int) $application->getId();
        $applicationTitle = $application->getJobOffer()->getTitle();
        $sender = $recruiterEmail->getSender();
        $subject = $recruiterEmail->getSubject();

        return new NotificationItem(
            id: (int) $recruiterEmail->getId(),
            type: 'email_received',
            title: 'New recruiter email',
            message: $subject,
            createdAt: $recruiterEmail->getReceivedAt()->format(DATE_ATOM),
            applicationId: $applicationId,
            applicationTitle: $applicationTitle,
            sender: $sender,
            subject: $subject,
            isSeen: $recruiterEmail->isSeen(),
            href: '/applications/'.$applicationId,
        );
    }

    public function createFromApplicationHistory(ApplicationHistory $history): NotificationItem
    {
        $application = $history->getApplication();
        $applicationId = (int) $application->getId();
        $applicationTitle = $application->getJobOffer()->getTitle();
        $description = $history->getDescription() ?? 'Application created from the browser extension';

        return new NotificationItem(
            id: (int) $history->getId(),
            type: 'imported_from_extension',
            title: 'Application imported',
            message: $description,
            createdAt: $history->getCreatedAt()->format(DATE_ATOM),
            applicationId: $applicationId,
            applicationTitle: $applicationTitle,
            sender: 'Browser extension',
            subject: $description,
            isSeen: $history->isSeen(),
            href: '/applications/'.$applicationId,
        );
    }

    public function topicForUserId(int $userId): string
    {
        return self::TOPIC_PREFIX.$userId.':notifications';
    }
}
