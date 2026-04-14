<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RecruiterEmail;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecruiterEmail>
 */
final class RecruiterEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecruiterEmail::class);
    }

    /**
     * @return RecruiterEmail[]
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('recruiterEmail')
            ->andWhere('recruiterEmail.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('recruiterEmail.receivedAt', 'DESC')
            ->addOrderBy('recruiterEmail.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function isAlreadyProcessed(string $messageId, int $userId): bool
    {
        return null !== $this->createQueryBuilder('recruiterEmail')
            ->select('1')
            ->andWhere('recruiterEmail.messageId = :messageId')
            ->andWhere('IDENTITY(recruiterEmail.owner) = :userId')
            ->setParameter('messageId', $messageId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
