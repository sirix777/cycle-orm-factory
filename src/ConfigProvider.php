<?php

declare(strict_types=1);

namespace Sirix\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\ORMInterface;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Internal\MigrationsToggle;
use Sirix\Cycle\Internal\PackageChecker;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\SchemaCompilerInterface;

final class ConfigProvider
{
    /**
     * @return array{
     *     dependencies: array{
     *         aliases: array<string, string>,
     *         invokables: array<string, string>,
     *         factories: array<string, string>
     *     },
     *     laminas-cli: array{
     *         commands: array<string, class-string>
     *     }
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli' => $this->getCliConfig(),
        ];
    }

    /**
     * @return array{
     *     aliases: array<string, string>,
     *     invokables: array<string, string>,
     *     factories: array<string, string>
     * }
     */
    public function getDependencies(): array
    {
        $factories = [
            'orm' => CycleFactory::class,
            'migrator' => MigratorFactory::class,
            'dbal' => DbalFactory::class,
            Service\MigratorService::class => Service\MigratorServiceFactory::class,
            Service\SchemaCompilerService::class => Service\SchemaCompilerServiceFactory::class,
        ];

        if (PackageChecker::isConsoleAvailable()) {
            $factories[Command\Cycle\ClearCycleSchemaCache::class] = Command\Cycle\ClearCycleSchemaCacheFactory::class;
            $factories[Command\Cycle\SchemaSyncCommand::class] = Command\Cycle\SchemaSyncCommandFactory::class;

            if (PackageChecker::isEntityBehaviorAvailable()) {
                $factories[Command\Cycle\SchemaCompileCommand::class] = Command\Cycle\SchemaCompileCommandFactory::class;
            }
        }

        if (PackageChecker::isConsoleAvailable() && MigrationsToggle::areMigrationsEnabled()) {
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
                DatabaseInterface::class => 'dbal',
                MigratorInterface::class => 'migrator',
                ORMInterface::class => 'orm',
                SchemaCompilerInterface::class => Service\SchemaCompilerService::class,
            ],
            'invokables' => [
                Service\CompiledSchemaStorage::class => Service\CompiledSchemaStorage::class,
            ],
            'factories' => $factories,
        ];
    }

    /**
     * @return array{commands: array<string, class-string>}
     */
    private function getCliConfig(): array
    {
        if (! PackageChecker::isConsoleAvailable()) {
            return ['commands' => []];
        }

        $commands = [
            CommandName::ClearCache->value => Command\Cycle\ClearCycleSchemaCache::class,
            CommandName::SchemaSync->value => Command\Cycle\SchemaSyncCommand::class,
        ];

        if (PackageChecker::isEntityBehaviorAvailable()) {
            $commands[CommandName::SchemaCompile->value] = Command\Cycle\SchemaCompileCommand::class;
        }

        if (MigrationsToggle::areMigrationsEnabled()) {
            $commands[CommandName::RunMigration->value] = Command\Migrator\MigrateCommand::class;
            $commands[CommandName::RollbackMigration->value] = Command\Migrator\RollbackCommand::class;
            $commands[CommandName::GenerateMigration->value] = Command\Migrator\CreateMigrationCommand::class;
            $commands[CommandName::GenerateSeed->value] = Command\Migrator\CreateSeedCommand::class;
            $commands[CommandName::RunSeed->value] = Command\Migrator\SeedCommand::class;

            if (PackageChecker::isGenerateMigrationsAvailable()) {
                $commands[CommandName::SchemaMigrationsGenerate->value] = Command\Cycle\SchemaMigrationsGenerateCommand::class;
            }
        }

        return [
            'commands' => $commands,
        ];
    }
}
