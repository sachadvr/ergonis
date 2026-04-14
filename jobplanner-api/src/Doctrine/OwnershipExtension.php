<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class OwnershipExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addOwnershipConstraint($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addOwnershipConstraint($queryBuilder, $resourceClass);
    }

    private function addOwnershipConstraint(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        if (!OwnedEntities::isOwned($resourceClass)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $field = OwnedEntities::getOwnerField($resourceClass);
        $queryBuilder->andWhere($rootAlias.'.'.$field.' = :current_user')
            ->setParameter('current_user', $user);
    }
}
