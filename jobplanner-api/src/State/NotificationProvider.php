<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\NotificationItem;
use App\Entity\User;
use App\Repository\ApplicationHistoryRepository;
use App\Repository\RecruiterEmailRepository;
use App\Service\NotificationFactory;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class NotificationProvider implements ProviderInterface
{
    public function __construct(
        private RecruiterEmailRepository $recruiterEmailRepository,
        private ApplicationHistoryRepository $applicationHistoryRepository,
        private NotificationFactory $notificationFactory,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            return [];
        }

        $notifications = array_merge(
            array_map(
                fn (\App\Entity\RecruiterEmail $recruiterEmail): NotificationItem => $this->notificationFactory->createFromRecruiterEmail($recruiterEmail),
                $this->recruiterEmailRepository->findRecentByUser($user, 10)
            ),
            array_map(
                fn (\App\Entity\ApplicationHistory $applicationHistory): NotificationItem => $this->notificationFactory->createFromApplicationHistory($applicationHistory),
                $this->applicationHistoryRepository->findRecentByUser($user, 10)
            )
        );

        usort($notifications, static fn (NotificationItem $left, NotificationItem $right): int => strtotime($right->createdAt) <=> strtotime($left->createdAt));

        return array_values(array_slice($notifications, 0, 10));
    }
}
