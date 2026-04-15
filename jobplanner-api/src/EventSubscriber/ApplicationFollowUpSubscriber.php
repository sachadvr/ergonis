<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Application;
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

        foreach (array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates()) as $entity) {
            if (!$entity instanceof Application) {
                continue;
            }

            $followUps = $this->planningService->scheduleFollowUpsForApplication($entity);
            if ([] === $followUps) {
                continue;
            }

            $metadata = $entityManager->getClassMetadata(ScheduledFollowUp::class);
            foreach ($followUps as $followUp) {
                $entityManager->persist($followUp);
                $unitOfWork->computeChangeSet($metadata, $followUp);
            }
        }
    }
}
