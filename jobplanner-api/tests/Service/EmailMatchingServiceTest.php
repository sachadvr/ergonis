<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\RecruiterEmailRepository;
use App\Service\EmailMatchingService;
use App\Tests\Support\DoctrineTestHarness;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class EmailMatchingServiceTest extends TestCase
{
    use DoctrineTestHarness;

    private EntityManagerInterface $entityManager;
    private EmailMatchingService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createInMemoryEntityManager();
        $registry = $this->createManagerRegistry($this->entityManager);
        $this->service = new EmailMatchingService(
            new ApplicationRepository($registry),
            new RecruiterEmailRepository($registry),
        );
    }

    public function testFindMatchingApplicationByRecruiterEmail(): void
    {
        [$user, $application] = $this->createApplicationWithJobOffer('Recruiter', 'Acme', 'recruiter@example.com');

        $match = $this->service->findMatchingApplication('Recruiter <recruiter@example.com>', 'Subject', 'Body', $user->getId());

        $this->assertNotNull($match);
        $this->assertSame($application->getId(), $match->getId());
    }

    public function testFindMatchingApplicationByCompanyTextAndProcessedLookup(): void
    {
        [$user, $application] = $this->createApplicationWithJobOffer('Backend Engineer', 'Acme Corp', null);

        $match = $this->service->findMatchingApplication('someone@other.com', 'We contacted Acme Corp', 'for the backend engineer role', $user->getId());

        $this->assertNotNull($match);
        $this->assertSame($application->getId(), $match->getId());

        $recruiterEmail = (new RecruiterEmail())
            ->setApplication($application)
            ->setOwner($user)
            ->setMessageId('msg-123')
            ->setSender('recruiter@example.com')
            ->setSubject('Interview')
            ->setBody('Hello')
            ->setReceivedAt(new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')))
            ->setIsSeen(false)
            ->setDirection('INCOMING');

        $this->entityManager->persist($recruiterEmail);
        $this->entityManager->flush();

        $this->assertTrue($this->service->isAlreadyProcessed('msg-123', $user->getId()));
        $this->assertFalse($this->service->isAlreadyProcessed('msg-missing', $user->getId()));
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createApplicationWithJobOffer(string $title, string $company, ?string $recruiterEmail): array
    {
        $user = (new User())
            ->setEmail('candidate@example.com')
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
