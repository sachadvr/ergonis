<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

trait DoctrineTestHarness
{
    protected function createInMemoryEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([
            dirname(__DIR__, 2).'/src/Entity',
        ], true);
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
    }

    protected function createManagerRegistry(EntityManager $entityManager): ManagerRegistry
    {
        return new class($entityManager) implements ManagerRegistry {
            public function __construct(private readonly EntityManager $entityManager)
            {
            }

            public function getDefaultManagerName(): string
            {
                return 'default';
            }

            public function getManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getManagers(): array
            {
                return ['default' => $this->entityManager];
            }

            public function resetManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getManagerNames(): array
            {
                return ['default' => 'default'];
            }

            public function getRepository(string $persistentObject, ?string $persistentManagerName = null): ObjectRepository
            {
                return $this->entityManager->getRepository($persistentObject);
            }

            public function getManagerForClass(string $class): ?ObjectManager
            {
                return $this->entityManager;
            }

            public function getDefaultConnectionName(): string
            {
                return 'default';
            }

            public function getConnection(?string $name = null): object
            {
                return $this->entityManager->getConnection();
            }

            public function getConnections(): array
            {
                return ['default' => $this->entityManager->getConnection()];
            }

            public function getConnectionNames(): array
            {
                return ['default' => 'default'];
            }
        };
    }

    protected function getDatabaseConnection(EntityManager $entityManager): Connection
    {
        return $entityManager->getConnection();
    }
}
