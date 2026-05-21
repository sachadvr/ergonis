<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\Interview;
use App\Entity\ScheduledFollowUp;
use App\Service\FollowUpPlanningService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final readonly class ApplicationFollowUpSubscriber
{
    public function __construct(
        private FollowUpPlanningService $planningService,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $applicationMetadata = $entityManager->getClassMetadata(Application::class);
        $followUpMetadata = $entityManager->getClassMetadata(ScheduledFollowUp::class);

        foreach (array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates()) as $entity) {
            if ($entity instanceof Application) {
                if (null === $entity->getAppliedAt() && ApplicationStatus::APPLIED === $entity->getStatus()) {
                    $entity->setAppliedAt(new \DateTimeImmutable());
                    $unitOfWork->recomputeSingleEntityChangeSet($applicationMetadata, $entity);
                }

                $status = $entity->getStatus();
                $keep = $entity->isFollowUpsEnabled()
                    && \in_array($status, [ApplicationStatus::APPLIED, ApplicationStatus::INTERVIEW], true);

                if (!$keep) {
                    foreach ($entity->getScheduledFollowUps() as $existing) {
                        if (ScheduledFollowUp::STATUS_PENDING !== $existing->getStatus()) {
                            continue;
                        }
                        $unitOfWork->scheduleForDelete($existing);
                    }
                    continue;
                }

                if (ApplicationStatus::APPLIED === $status) {
                    foreach ($entity->getScheduledFollowUps() as $existing) {
                        if (ScheduledFollowUp::STATUS_PENDING !== $existing->getStatus()) {
                            continue;
                        }
                        if (ScheduledFollowUp::TEMPLATE_THANK_YOU !== $existing->getTemplateType()) {
                            continue;
                        }
                        $unitOfWork->scheduleForDelete($existing);
                    }
                }

                $followUps = $this->planningService->scheduleFollowUpsForApplication($entity);

                if (ApplicationStatus::INTERVIEW === $status) {
                    foreach ($entity->getInterviews() as $interview) {
                        $followUps = array_merge($followUps, $this->planningService->scheduleThankYousForInterview($interview));
                    }
                }
            } elseif ($entity instanceof Interview) {
                foreach ($entity->getApplication()->getScheduledFollowUps() as $existing) {
                    if (ScheduledFollowUp::STATUS_PENDING !== $existing->getStatus()) {
                        continue;
                    }
                    if (ScheduledFollowUp::TEMPLATE_FOLLOW_UP !== $existing->getTemplateType()) {
                        continue;
                    }
                    $unitOfWork->scheduleForDelete($existing);
                }
                $followUps = $this->planningService->scheduleThankYousForInterview($entity);
            } else {
                continue;
            }

            foreach ($followUps as $followUp) {
                $entityManager->persist($followUp);
                $unitOfWork->computeChangeSet($followUpMetadata, $followUp);
            }
        }
    }
}
