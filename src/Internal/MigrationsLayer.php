<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Psr\Container\ContainerInterface;
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
     *     factories: array<string, class-string<callable(ContainerInterface, string, null|array<mixed>): mixed&object>>
     * }
     */
    public function getDependencies(): array
    {
        $factories = [
            'migrator'                     => MigratorFactory::class,
            Service\MigratorService::class => Service\MigratorServiceFactory::class,
        ];

        foreach ($this->getCommandRegistrations() as $registration) {
            $factories[$registration['command']] = $registration['factory'];
        }

        return [
            'aliases'   => [
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
        $commands = [];

        foreach ($this->getCommandRegistrations() as $name => $registration) {
            $commands[$name] = $registration['command'];
        }

        return $commands;
    }

    /**
     * @return array<string, array{
     *     command: class-string,
     *     factory: class-string<callable(ContainerInterface, string, null|array<mixed>): mixed&object>
     * }>
     */
    private function getCommandRegistrations(): array
    {
        if (! PackageChecker::isConsoleAvailable()) {
            return [];
        }

        $registrations = [
            CommandName::MigrationRun->value      => [
                'command' => Command\Migrator\MigrateCommand::class,
                'factory' => Command\Migrator\MigrateCommandFactory::class,
            ],
            CommandName::MigrationRollback->value => [
                'command' => Command\Migrator\RollbackCommand::class,
                'factory' => Command\Migrator\RollbackCommandFactory::class,
            ],
            CommandName::MigrationCreate->value   => [
                'command' => Command\Migrator\CreateMigrationCommand::class,
                'factory' => Command\Migrator\CreateMigrationCommandFactory::class,
            ],
            CommandName::SeedCreate->value        => [
                'command' => Command\Migrator\CreateSeedCommand::class,
                'factory' => Command\Migrator\CreateSeedCommandFactory::class,
            ],
            CommandName::SeedRun->value           => [
                'command' => Command\Migrator\SeedCommand::class,
                'factory' => Command\Migrator\SeedCommandFactory::class,
            ],
        ];

        if (PackageChecker::isGenerateMigrationsAvailable() && PackageChecker::isEntityBehaviorAvailable()) {
            $registrations[CommandName::SchemaMigrationGenerate->value] = [
                'command' => Command\Cycle\SchemaMigrationsGenerateCommand::class,
                'factory' => Command\Cycle\SchemaMigrationsGenerateCommandFactory::class,
            ];
        }

        return $registrations;
    }
}
