<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
final class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * @return Application[]
     */
    public function findActiveByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.jobOffer', 'j')
            ->addSelect('COALESCE(a.lastActivityAt, a.createdAt) AS HIDDEN sortActivity')
            ->where('a.owner = :userId')
            ->andWhere('a.status NOT IN (:terminalStatuses)')
            ->setParameter('userId', $userId)
            ->setParameter('terminalStatuses', [ApplicationStatus::REJECTED, ApplicationStatus::ACCEPTED])
            ->orderBy('sortActivity', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Application[]
     */
    public function findActiveByUserEntity(User $user): array
    {
        return $this->findActiveByUser((int) $user->getId());
    }
}
