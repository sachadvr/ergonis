<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final readonly class ApplicationInterviewCleanupSubscriber
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Application) {
                continue;
            }

            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (!isset($changeSet['status'])) {
                continue;
            }

            $newStatus = $changeSet['status'][1] ?? null;
            if ($newStatus instanceof ApplicationStatus) {
                $newStatus = $newStatus->value;
            }

            if (!\in_array($newStatus, [ApplicationStatus::WISHLIST->value, ApplicationStatus::APPLIED->value], true)) {
                continue;
            }

            foreach ($entity->getInterviews() as $interview) {
                $entityManager->remove($interview);
            }

            $entity->clearInterviews();
        }
    }
}
