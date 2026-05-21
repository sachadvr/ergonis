<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\FollowUpRule;
use App\Entity\Interview;
use App\Entity\ScheduledFollowUp;
use App\Repository\FollowUpRuleRepository;

/**
 * Plans the automatic follow-ups according to the user's rules.
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

        if (!$application->getInterviews()->isEmpty()) {
            return [];
        }

        $rules = $this->followUpRuleRepository->findEnabledByUserAndTemplate($owner, FollowUpRule::TEMPLATE_FOLLOW_UP);
        $existingScheduledDates = $this->getExistingScheduledDates($application);
        $followUps = [];

        foreach ($rules as $rule) {
            $days = $rule->getDaysWithoutReply();
            $scheduledAt = $appliedAt->modify("+{$days} days")->setTime(10, 0, 0);

            if (\in_array($scheduledAt->format('Y-m-d'), $existingScheduledDates, true)) {
                continue;
            }

            if ($scheduledAt <= new \DateTimeImmutable()) {
                continue;
            }

            $followUp = new ScheduledFollowUp();
            $followUp->setApplication($application);
            $followUp->setScheduledAt($scheduledAt);
            $followUp->setStatus(ScheduledFollowUp::STATUS_PENDING);
            $followUp->setTemplateType(ScheduledFollowUp::TEMPLATE_FOLLOW_UP);
            $followUps[] = $followUp;
        }

        return $followUps;
    }

    /**
     * @return ScheduledFollowUp[]
     */
    public function scheduleThankYousForInterview(Interview $interview): array
    {
        $application = $interview->getApplication();
        $owner = $application->getOwner();
        if (null === $owner) {
            return [];
        }

        if (!$application->isFollowUpsEnabled()) {
            return [];
        }

        $rules = $this->followUpRuleRepository->findEnabledByUserAndTemplate($owner, FollowUpRule::TEMPLATE_THANK_YOU);
        if ([] === $rules) {
            return [];
        }

        $interviewAt = $interview->getScheduledAt();
        $existingScheduledDates = $this->getExistingScheduledDates($application);
        $followUps = [];

        foreach ($rules as $rule) {
            $days = $rule->getDaysWithoutReply();
            $scheduledAt = $interviewAt->modify("+{$days} days")->setTime(10, 0, 0);

            if (\in_array($scheduledAt->format('Y-m-d'), $existingScheduledDates, true)) {
                continue;
            }

            if ($scheduledAt <= new \DateTimeImmutable()) {
                continue;
            }

            $followUp = new ScheduledFollowUp();
            $followUp->setApplication($application);
            $followUp->setScheduledAt($scheduledAt);
            $followUp->setStatus(ScheduledFollowUp::STATUS_PENDING);
            $followUp->setTemplateType(ScheduledFollowUp::TEMPLATE_THANK_YOU);
            $followUps[] = $followUp;
        }

        return $followUps;
    }

    private function shouldScheduleFollowUps(Application $application): bool
    {
        if (!$application->isFollowUpsEnabled()) {
            return false;
        }

        $status = $application->getStatus();

        return ApplicationStatus::APPLIED === $status;
    }

    /**
     * @return string[]
     */
    private function getExistingScheduledDates(Application $application): array
    {
        $dates = [];

        foreach ($application->getScheduledFollowUps() as $fu) {
            if (ScheduledFollowUp::STATUS_PENDING !== $fu->getStatus()) {
                continue;
            }
            $dates[] = $fu->getScheduledAt()->format('Y-m-d');
        }

        return $dates;
    }
}
