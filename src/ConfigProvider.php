<?php

declare(strict_types=1);

namespace Sirix\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\ORMInterface;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Service\MigratorInterface;

final class ConfigProvider
{
    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli' => $this->getCliConfig(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                DatabaseInterface::class => 'dbal',
                MigratorInterface::class => 'migrator',
                ORMInterface::class => 'orm',
            ],
            'invokables' => [],
            'factories' => [
                'orm' => CycleFactory::class,
                'migrator' => MigratorFactory::class,
                'dbal' => DbalFactory::class,
                Service\MigratorService::class => Service\MigratorServiceFactory::class,
                Command\Migrator\MigrateCommand::class => Command\Migrator\MigrateCommandFactory::class,
                Command\Migrator\RollbackCommand::class => Command\Migrator\RollbackCommandFactory::class,
                Command\Migrator\CreateMigrationCommand::class => Command\Migrator\CreateMigrationCommandFactory::class,
                Command\Migrator\CreateSeedCommand::class => Command\Migrator\CreateSeedCommandFactory::class,
                Command\Migrator\SeedCommand::class => Command\Migrator\SeedCommandFactory::class,
                Command\Cycle\ClearCycleSchemaCache::class => Command\Cycle\ClearCycleSchemaCacheFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getCliConfig(): array
    {
        return [
            'commands' => [
                CommandName::RunMigration->value => Command\Migrator\MigrateCommand::class,
                CommandName::RollbackMigration->value => Command\Migrator\RollbackCommand::class,
                CommandName::GenerateMigration->value => Command\Migrator\CreateMigrationCommand::class,
                CommandName::ClearCache->value => Command\Cycle\ClearCycleSchemaCache::class,
                CommandName::GenerateSeed->value => Command\Migrator\CreateSeedCommand::class,
                CommandName::RunSeed->value => Command\Migrator\SeedCommand::class,
            ],
        ];
    }
}
