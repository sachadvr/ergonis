<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\ScheduledFollowUp;
use App\Entity\User;
use App\Entity\UserMailboxSettings;
use App\Mailer\UserMailerService;
use App\Mailer\UserSmtpTransportFactory;
use App\Repository\UserMailboxSettingsRepository;
use App\Service\Ai\AiServiceInterface;
use App\Service\FollowUpProcessorService;
use App\Tests\Support\DoctrineTestHarness;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class FollowUpProcessorServiceTest extends TestCase
{
    use DoctrineTestHarness;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createInMemoryEntityManager();
    }

    public function testProcessCancelsWhenRecruiterEmailMissing(): void
    {
        $followUp = $this->createFollowUp(null);
        $service = $this->createService($followUp->getApplication()->getOwner());

        $service->process($followUp);

        $this->assertSame(ScheduledFollowUp::STATUS_CANCELLED, $followUp->getStatus());
        $this->assertNotNull($followUp->getCancelledAt());
    }

    public function testProcessGeneratesContentAndMarksAsSent(): void
    {
        $followUp = $this->createFollowUp('recruiter@example.com');
        $aiService = $this->createMock(AiServiceInterface::class);
        $aiService->expects($this->once())
            ->method('generateFollowUpEmail')
            ->willReturn('Follow-up email content');

        $service = $this->createService($followUp->getApplication()->getOwner(), $aiService);
        $service->process($followUp);

        $this->assertSame(ScheduledFollowUp::STATUS_SENT, $followUp->getStatus());
        $this->assertSame('Follow-up email content', $followUp->getGeneratedContent());
    }

    public function testProcessReusesExistingContent(): void
    {
        $followUp = $this->createFollowUp('recruiter@example.com');
        $followUp->setGeneratedContent('Already written');

        $aiService = $this->createMock(AiServiceInterface::class);
        $aiService->expects($this->never())->method('generateFollowUpEmail');

        $service = $this->createService($followUp->getApplication()->getOwner(), $aiService);
        $service->process($followUp);

        $this->assertSame(ScheduledFollowUp::STATUS_SENT, $followUp->getStatus());
        $this->assertSame('Already written', $followUp->getGeneratedContent());
    }

    private function createService(User $owner, ?AiServiceInterface $aiService = null): FollowUpProcessorService
    {
        $logger = $this->createStub(LoggerInterface::class);
        $registry = $this->createManagerRegistry($this->entityManager);
        $settingsRepository = new UserMailboxSettingsRepository($registry);

        $settings = (new UserMailboxSettings())
            ->setOwner($owner)
            ->setSmtpHost('mailpit')
            ->setSmtpPort(1025)
            ->setSmtpEncryption('none')
            ->setSmtpUser('candidate@example.com')
            ->setSmtpPassword('secret');
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $mailer = new UserMailerService(
            $settingsRepository,
            new UserSmtpTransportFactory($logger),
            $logger,
        );

        return new FollowUpProcessorService(
            $this->entityManager,
            $aiService ?? $this->createStub(AiServiceInterface::class),
            $mailer,
        );
    }

    private function createFollowUp(?string $recruiterEmail): ScheduledFollowUp
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->entityManager->persist($owner);
        $this->entityManager->flush();

        $jobOffer = (new JobOffer())
            ->setTitle('Backend Engineer')
            ->setCompany('Acme')
            ->setRecruiterContactEmail($recruiterEmail);

        $application = (new Application())
            ->setJobOffer($jobOffer)
            ->setOwner($owner)
            ->setStatus(ApplicationStatus::APPLIED);

        $followUp = (new ScheduledFollowUp())
            ->setApplication($application)
            ->setStatus(ScheduledFollowUp::STATUS_PENDING);

        return $followUp;
    }
}
