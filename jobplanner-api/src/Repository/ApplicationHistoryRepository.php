<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicationHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicationHistory>
 */
final class ApplicationHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationHistory::class);
    }

    /**
     * @return ApplicationHistory[]
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('applicationHistory')
            ->innerJoin('applicationHistory.application', 'application')
            ->andWhere('application.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('applicationHistory.createdAt', 'DESC')
            ->addOrderBy('applicationHistory.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
