<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FollowUpRule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FollowUpRule>
 */
final class FollowUpRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FollowUpRule::class);
    }

    /**
     * @return FollowUpRule[]
     */
    public function findEnabledByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.owner = :user')
            ->andWhere('r.enabled = :enabled')
            ->setParameter('user', $user)
            ->setParameter('enabled', true)
            ->orderBy('r.daysWithoutReply', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
