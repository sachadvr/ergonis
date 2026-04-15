<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use App\Entity\ApplicationHistory;
use App\Entity\ApplicationHistoryActionType;
use App\Entity\RecruiterEmail;
use App\Entity\ScheduledFollowUp;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class EmailSyncProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailMatchingService $emailMatchingService,
        private AiServiceInterface $aiService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $emails
     */
    public function process(array $emails, int $userId, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $newSyncAt = $this->calculateNextSyncAt($emails, $fallback);

        foreach ($emails as $email) {
            $this->processEmail($email, $userId);
        }

        return $newSyncAt;
    }

    /**
     * @param array<int, array<string, mixed>> $emails
     */
    private function calculateNextSyncAt(array $emails, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $latest = $fallback;
        $foundDate = false;

        foreach ($emails as $email) {
            $date = $this->readEmailField($email, 'date');
            if (null === $date) {
                continue;
            }

            try {
                $messageDate = new \DateTimeImmutable($date);
                if ($messageDate > $latest) {
                    $latest = $messageDate;
                }
                $foundDate = true;
            } catch (\Throwable) {
                continue;
            }
        }

        if (!$foundDate) {
            return new \DateTimeImmutable();
        }

        return $latest;
    }

    private function processEmail(array $email, int $userId): void
    {
        $messageId = $this->readEmailField($email, 'messageId')
            ?? md5(($this->readEmailField($email, 'date') ?? '').($this->readEmailField($email, 'subject') ?? ''));

        $this->logger->debug('Processing email', ['messageId' => $messageId, 'userId' => $userId]);

        if ($this->emailMatchingService->isAlreadyProcessed($messageId, $userId)) {
            $this->logger->debug('Email already processed', ['messageId' => $messageId]);

            return;
        }

        $body = $this->readEmailField($email, 'textPlain')
            ?? strip_tags((string) ($this->readEmailField($email, 'textHtml') ?? ''));

        $this->logger->debug('Email metadata', [
            'from' => $this->readEmailField($email, 'fromAddress'),
            'subject' => $this->readEmailField($email, 'subject'),
        ]);

        $application = $this->emailMatchingService->findMatchingApplication(
            $this->readEmailField($email, 'fromAddress') ?? '',
            $this->readEmailField($email, 'subject') ?? '',
            $body,
            $userId
        );

        if (null === $application) {
            $this->logger->debug('No matching application found', ['messageId' => $messageId]);

            return;
        }

        $recruiterEmail = new RecruiterEmail();
        $recruiterEmail->setApplication($application);
        $recruiterEmail->setSender($this->readEmailField($email, 'fromAddress') ?? '');
        $recruiterEmail->setSubject($this->readEmailField($email, 'subject') ?? '');
        $recruiterEmail->setBody($body);
        $recruiterEmail->setMessageId($messageId);
        $recruiterEmail->setReceivedAt(new \DateTimeImmutable($this->readEmailField($email, 'date') ?? 'now'));
        $recruiterEmail->setOwner($this->entityManager->getReference(User::class, $userId));

        $recruiterEmail->setAiSummary($this->aiService->summarizeEmail($recruiterEmail->getBody()));

        $this->entityManager->persist($recruiterEmail);

        $history = new ApplicationHistory();
        $history->setApplication($application);
        $history->setActionType(ApplicationHistoryActionType::EMAIL_RECEIVED);
        $history->setDescription('Email reçu de '.$recruiterEmail->getSender());
        $this->entityManager->persist($history);

        $application->setLastActivityAt(new \DateTimeImmutable());
        $this->cancelPendingFollowUps($application);

        $this->entityManager->flush();

        $this->logger->info('Email processed', [
            'messageId' => $messageId,
            'applicationId' => $application->getId(),
        ]);
    }

    private function cancelPendingFollowUps(Application $application): void
    {
        foreach ($application->getScheduledFollowUps() as $followUp) {
            if (ScheduledFollowUp::STATUS_PENDING === $followUp->getStatus()) {
                $followUp->setStatus(ScheduledFollowUp::STATUS_CANCELLED);
                $followUp->setCancelledAt(new \DateTimeImmutable());
            }
        }
    }

    /**
     * @param array<string, mixed> $email
     */
    private function readEmailField(array $email, string $field): ?string
    {
        $value = $email[$field] ?? null;

        return is_string($value) ? $value : null;
    }
}
