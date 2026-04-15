<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\RecruiterEmail;
use App\Service\MercureNotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class RecruiterEmailNotificationSubscriber
{
    /**
     * @var RecruiterEmail[]
     */
    private array $pendingRecruiterEmails = [];

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
            if (!$entity instanceof RecruiterEmail) {
                continue;
            }

            if ('INCOMING' !== $entity->getDirection()) {
                continue;
            }

            if (null === $entity->getOwner()) {
                continue;
            }

            $this->pendingRecruiterEmails[] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingRecruiterEmails) {
            return;
        }

        foreach ($this->pendingRecruiterEmails as $recruiterEmail) {
            $this->mercureNotificationPublisher->publishRecruiterEmail($recruiterEmail);
        }

        $this->pendingRecruiterEmails = [];
    }
}
