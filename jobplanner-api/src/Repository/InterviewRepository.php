<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Interview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interview>
 */
final class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    /**
     * Entretiens dans les X prochaines heures n'ayant pas encore reçu de rappel.
     *
     * @return Interview[]
     */
    public function findUpcomingNeedingReminder(int $hoursAhead = 24): array
    {
        $now = new \DateTimeImmutable();
        $windowEnd = $now->modify("+{$hoursAhead} hours");

        return $this->createQueryBuilder('i')
            ->where('i.reminderSent = :sent')
            ->andWhere('i.scheduledAt > :now')
            ->andWhere('i.scheduledAt <= :windowEnd')
            ->setParameter('sent', false)
            ->setParameter('now', $now)
            ->setParameter('windowEnd', $windowEnd)
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
