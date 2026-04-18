<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\FollowUpRule;
use App\Entity\JobOffer;
use App\Entity\ScheduledFollowUp;
use App\Entity\User;
use App\Repository\FollowUpRuleRepository;
use App\Service\FollowUpPlanningService;
use App\Tests\Support\DoctrineTestHarness;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class FollowUpPlanningServiceTest extends TestCase
{
    use DoctrineTestHarness;

    private EntityManagerInterface $entityManager;
    private FollowUpPlanningService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createInMemoryEntityManager();
        $registry = $this->createManagerRegistry($this->entityManager);
        $this->service = new FollowUpPlanningService(new FollowUpRuleRepository($registry));
    }

    public function testScheduleFollowUpsSkipsDuplicatesAndPastRules(): void
    {
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $jobOffer = (new JobOffer())->setTitle('Backend Engineer')->setCompany('Acme');
        $application = (new Application())
            ->setOwner($user)
            ->setJobOffer($jobOffer)
            ->setStatus(ApplicationStatus::APPLIED)
            ->setAppliedAt(new \DateTimeImmutable('2099-04-01 09:00:00', new \DateTimeZone('UTC')));

        $existingFollowUp = (new ScheduledFollowUp())
            ->setApplication($application)
            ->setScheduledAt(new \DateTimeImmutable('2099-04-08 10:00:00', new \DateTimeZone('UTC')))
            ->setStatus(ScheduledFollowUp::STATUS_PENDING);
        $application->getScheduledFollowUps()->add($existingFollowUp);

        $rule7 = (new FollowUpRule())->setOwner($user)->setDaysWithoutReply(7)->setEnabled(true);
        $rule14 = (new FollowUpRule())->setOwner($user)->setDaysWithoutReply(14)->setEnabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->persist($jobOffer);
        $this->entityManager->persist($application);
        $this->entityManager->persist($rule7);
        $this->entityManager->persist($rule14);
        $this->entityManager->flush();

        $followUps = $this->service->scheduleFollowUpsForApplication($application);

        $this->assertCount(1, $followUps);
        $this->assertSame(ScheduledFollowUp::STATUS_PENDING, $followUps[0]->getStatus());
        $this->assertSame('2099-04-15 10:00:00', $followUps[0]->getScheduledAt()->format('Y-m-d H:i:s'));
    }

    public function testScheduleFollowUpsReturnsEmptyWhenStatusIsNotEligible(): void
    {
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $jobOffer = (new JobOffer())->setTitle('Backend Engineer')->setCompany('Acme');
        $application = (new Application())
            ->setOwner($user)
            ->setJobOffer($jobOffer)
            ->setStatus(ApplicationStatus::REJECTED)
            ->setAppliedAt(new \DateTimeImmutable('2099-04-01 09:00:00', new \DateTimeZone('UTC')));

        $this->entityManager->persist($user);
        $this->entityManager->persist($jobOffer);
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        $this->assertSame([], $this->service->scheduleFollowUpsForApplication($application));
    }
}
