<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\MailboxSettingsProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMailboxSettings>
 */
final class UserMailboxSettingsRepository extends ServiceEntityRepository implements MailboxSettingsProviderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMailboxSettings::class);
    }

    public function findByUserId(int $userId): ?UserMailboxSettings
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserMailboxSettings[]
     */
    public function findActiveForSync(): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
