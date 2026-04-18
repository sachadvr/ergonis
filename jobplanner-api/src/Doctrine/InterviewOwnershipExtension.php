<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Interview;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Filters the interviews to only display those related to the authenticated user's applications.
 * Because OwnershipExtension is only applied to direct owner_id fields.
 */
final class InterviewOwnershipExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (Interview::class !== $resourceClass) {
            return;
        }
        $this->addOwnershipConstraint($queryBuilder, $queryNameGenerator);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        if (Interview::class !== $resourceClass) {
            return;
        }
        $this->addOwnershipConstraint($queryBuilder, $queryNameGenerator);
    }

    private function addOwnershipConstraint(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $appAlias = $queryNameGenerator->generateJoinAlias('application');
        $queryBuilder->join("{$rootAlias}.application", $appAlias)
            ->andWhere("{$appAlias}.owner = :current_user")
            ->setParameter('current_user', $user);
    }
}
