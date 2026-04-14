<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ScheduledFollowUp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduledFollowUp>
 */
final class ScheduledFollowUpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledFollowUp::class);
    }

    /**
     * @return ScheduledFollowUp[]
     */
    public function findPendingDueNow(): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.application', 'a')
            ->where('s.status = :status')
            ->andWhere('s.scheduledAt <= :now')
            ->setParameter('status', ScheduledFollowUp::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
