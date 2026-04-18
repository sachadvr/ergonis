<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Entity\ScheduledFollowUp;
use App\Entity\User;
use App\Repository\ApplicationHistoryRepository;
use App\Repository\ApplicationRepository;
use App\Repository\RecruiterEmailRepository;
use App\Service\Ai\AiServiceInterface;
use App\Service\EmailMatchingService;
use App\Service\EmailSyncProcessor;
use App\Tests\Support\DoctrineTestHarness;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EmailSyncProcessorTest extends TestCase
{
    use DoctrineTestHarness;

    private EntityManagerInterface $entityManager;
    private RecruiterEmailRepository $recruiterEmailRepository;
    private ApplicationHistoryRepository $applicationHistoryRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createInMemoryEntityManager();
        $registry = $this->createManagerRegistry($this->entityManager);
        $this->recruiterEmailRepository = new RecruiterEmailRepository($registry);
        $this->applicationHistoryRepository = new ApplicationHistoryRepository($registry);
    }

    public function testProcessCreatesRecruiterEmailHistoryAndCancelsPendingFollowUps(): void
    {
        [$user, $application] = $this->createApplicationWithJobOffer('Backend Engineer', 'Acme', 'recruiter@example.com');
        $followUp = (new ScheduledFollowUp())
            ->setApplication($application)
            ->setScheduledAt(new \DateTimeImmutable('2026-04-25 10:00:00', new \DateTimeZone('UTC')))
            ->setStatus(ScheduledFollowUp::STATUS_PENDING);
        $application->getScheduledFollowUps()->add($followUp);

        $this->entityManager->persist($followUp);
        $this->entityManager->flush();

        $processorAiService = $this->createMock(AiServiceInterface::class);
        $processorAiService->expects($this->once())
            ->method('summarizeEmail')
            ->with('Hello')
            ->willReturn('summary');

        $registry = $this->createManagerRegistry($this->entityManager);
        $processor = new EmailSyncProcessor(
            $this->entityManager,
            new EmailMatchingService(new ApplicationRepository($registry), new RecruiterEmailRepository($registry)),
            $processorAiService,
            $this->createStub(LoggerInterface::class),
        );

        $processor->process([
            [
                'messageId' => 'msg-1',
                'fromAddress' => 'Recruiter <recruiter@example.com>',
                'subject' => 'Interview',
                'textPlain' => 'Hello',
                'date' => '2026-04-15 10:00:00',
            ],
        ], $user->getId(), new \DateTimeImmutable('2026-04-01 00:00:00', new \DateTimeZone('UTC')));

        $this->entityManager->refresh($application);
        $this->assertSame(ScheduledFollowUp::STATUS_CANCELLED, $followUp->getStatus());
        $this->assertNotNull($followUp->getCancelledAt());
        $this->assertCount(1, $this->recruiterEmailRepository->findRecentByUser($user, 10));
        $this->assertCount(1, $this->applicationHistoryRepository->findBy(['application' => $application]));
    }

    public function testProcessSkipsAlreadyProcessedEmails(): void
    {
        [$user, $application] = $this->createApplicationWithJobOffer('Backend Engineer', 'Acme', 'recruiter@example.com');

        $recruiterEmail = (new RecruiterEmail())
            ->setApplication($application)
            ->setOwner($user)
            ->setMessageId('msg-1')
            ->setSender('recruiter@example.com')
            ->setSubject('Interview')
            ->setBody('Hello')
            ->setReceivedAt(new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')))
            ->setIsSeen(false)
            ->setDirection('INCOMING');
        $this->entityManager->persist($recruiterEmail);
        $this->entityManager->flush();

        $processorAiService = $this->createMock(AiServiceInterface::class);
        $processorAiService->expects($this->never())->method('summarizeEmail');
        $registry = $this->createManagerRegistry($this->entityManager);
        $processor = new EmailSyncProcessor(
            $this->entityManager,
            new EmailMatchingService(new ApplicationRepository($registry), new RecruiterEmailRepository($registry)),
            $processorAiService,
            $this->createStub(LoggerInterface::class),
        );

        $result = $processor->process([
            [
                'messageId' => 'msg-1',
                'fromAddress' => 'Recruiter <recruiter@example.com>',
                'subject' => 'Interview',
                'textPlain' => 'Hello',
                'date' => '2026-04-15 10:00:00',
            ],
        ], $user->getId(), new \DateTimeImmutable('2026-04-01 00:00:00', new \DateTimeZone('UTC')));

        $this->assertSame('2026-04-15 10:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertCount(1, $this->recruiterEmailRepository->findRecentByUser($user, 10));
        $this->assertCount(0, $this->applicationHistoryRepository->findBy(['application' => $application]));
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createApplicationWithJobOffer(string $title, string $company, ?string $recruiterEmail): array
    {
        $user = (new User())
            ->setEmail('candidate+'.uniqid().'@example.com')
            ->setPassword('secret');
        $this->entityManager->persist($user);

        $jobOffer = (new JobOffer())
            ->setTitle($title)
            ->setCompany($company)
            ->setRecruiterContactEmail($recruiterEmail);
        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        $application = (new Application())
            ->setOwner($user)
            ->setJobOffer($jobOffer)
            ->setStatus(ApplicationStatus::APPLIED);
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return [$user, $application];
    }
}
