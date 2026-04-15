<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\ApplicationHistory;
use App\Entity\ApplicationHistoryActionType;
use App\Service\MercureNotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class ApplicationHistoryNotificationSubscriber
{
    /**
     * @var ApplicationHistory[]
     */
    private array $pendingHistories = [];

    public function __construct(
        private readonly MercureNotificationPublisher $mercureNotificationPublisher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof ApplicationHistory) {
                continue;
            }

            if (ApplicationHistoryActionType::IMPORTED_FROM_EXTENSION !== $entity->getActionType()) {
                continue;
            }

            $this->pendingHistories[] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingHistories) {
            return;
        }

        foreach ($this->pendingHistories as $history) {
            $this->mercureNotificationPublisher->publishApplicationHistory($history);
        }

        $this->pendingHistories = [];
    }
}
