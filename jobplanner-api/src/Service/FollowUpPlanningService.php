<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\ScheduledFollowUp;
use App\Repository\FollowUpRuleRepository;

/**
 * Planifie les relances automatiques selon les règles de l'utilisateur.
 */
final readonly class FollowUpPlanningService
{
    public function __construct(
        private FollowUpRuleRepository $followUpRuleRepository,
    ) {
    }

    /**
     * @return ScheduledFollowUp[]
     */
    public function scheduleFollowUpsForApplication(Application $application): array
    {
        $owner = $application->getOwner();
        if (null === $owner) {
            return [];
        }

        if (!$this->shouldScheduleFollowUps($application)) {
            return [];
        }

        $appliedAt = $application->getAppliedAt();
        if (null === $appliedAt) {
            return [];
        }

        $rules = $this->followUpRuleRepository->findEnabledByUser($owner);
        $existingDays = $this->getExistingScheduledDays($application);
        $followUps = [];

        foreach ($rules as $rule) {
            $days = $rule->getDaysWithoutReply();
            if (\in_array($days, $existingDays, true)) {
                continue;
            }

            $scheduledAt = $appliedAt->modify("+{$days} days")->setTime(10, 0, 0);
            if ($scheduledAt <= new \DateTimeImmutable()) {
                continue;
            }

            $followUp = new ScheduledFollowUp();
            $followUp->setApplication($application);
            $followUp->setScheduledAt($scheduledAt);
            $followUp->setStatus(ScheduledFollowUp::STATUS_PENDING);
            $followUps[] = $followUp;
        }

        return $followUps;
    }

    private function shouldScheduleFollowUps(Application $application): bool
    {
        $status = $application->getStatus();

        return ApplicationStatus::APPLIED === $status || ApplicationStatus::OFFER === $status;
    }

    /**
     * @return int[]
     */
    private function getExistingScheduledDays(Application $application): array
    {
        $days = [];
        $appliedAt = $application->getAppliedAt();
        if (null === $appliedAt) {
            return $days;
        }

        foreach ($application->getScheduledFollowUps() as $fu) {
            if (ScheduledFollowUp::STATUS_PENDING !== $fu->getStatus()) {
                continue;
            }
            $diff = $appliedAt->diff($fu->getScheduledAt());
            $days[] = (int) $diff->days;
        }

        return $days;
    }
}
