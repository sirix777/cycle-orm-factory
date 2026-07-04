<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Sirix\Cycle\Command;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Service;
use Sirix\Cycle\Service\MigratorInterface;

/**
 * @internal
 */
final class MigrationsLayer
{
    /**
     * @return array{
     *     aliases: array<string, string>,
     *     factories: array<string, string>
     * }
     */
    public function getDependencies(): array
    {
        $factories = [
            'migrator' => MigratorFactory::class,
            Service\MigratorService::class => Service\MigratorServiceFactory::class,
        ];

        if (PackageChecker::isConsoleAvailable()) {
            $factories[Command\Migrator\MigrateCommand::class] = Command\Migrator\MigrateCommandFactory::class;
            $factories[Command\Migrator\RollbackCommand::class] = Command\Migrator\RollbackCommandFactory::class;
            $factories[Command\Migrator\CreateMigrationCommand::class] = Command\Migrator\CreateMigrationCommandFactory::class;
            $factories[Command\Migrator\CreateSeedCommand::class] = Command\Migrator\CreateSeedCommandFactory::class;
            $factories[Command\Migrator\SeedCommand::class] = Command\Migrator\SeedCommandFactory::class;

            if (PackageChecker::isGenerateMigrationsAvailable() && PackageChecker::isEntityBehaviorAvailable()) {
                $factories[Command\Cycle\SchemaMigrationsGenerateCommand::class]
                    = Command\Cycle\SchemaMigrationsGenerateCommandFactory::class;
            }
        }

        return [
            'aliases' => [
                MigratorInterface::class => 'migrator',
            ],
            'factories' => $factories,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    public function getCommands(): array
    {
        if (! PackageChecker::isConsoleAvailable()) {
            return [];
        }

        $commands = [
            CommandName::MigrationRun->value => Command\Migrator\MigrateCommand::class,
            CommandName::MigrationRollback->value => Command\Migrator\RollbackCommand::class,
            CommandName::MigrationCreate->value => Command\Migrator\CreateMigrationCommand::class,
            CommandName::SeedCreate->value => Command\Migrator\CreateSeedCommand::class,
            CommandName::SeedRun->value => Command\Migrator\SeedCommand::class,
        ];

        if (PackageChecker::isGenerateMigrationsAvailable() && PackageChecker::isEntityBehaviorAvailable()) {
            $commands[CommandName::SchemaMigrationGenerate->value] = Command\Cycle\SchemaMigrationsGenerateCommand::class;
        }

        return $commands;
    }
}
