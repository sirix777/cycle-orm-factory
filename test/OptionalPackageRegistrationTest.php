<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\ORMInterface;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command;
use Sirix\Cycle\ConfigProvider;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DatabaseFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Factory\NamedDatabaseAbstractFactory;
use Sirix\Cycle\Internal\MigrationsLayer;
use Sirix\Cycle\Internal\PackageChecker;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\MigratorServiceFactory;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;
use Sirix\Cycle\Service\SchemaCompilerServiceFactory;

use function class_exists;
use function getenv;

final class OptionalPackageRegistrationTest extends TestCase
{
    public function testProviderRegistrationsMatchAvailablePackages(): void
    {
        $console   = PackageChecker::isConsoleAvailable();
        $behavior  = PackageChecker::isEntityBehaviorAvailable();
        $migrator  = PackageChecker::isMigratorAvailable();
        $generator = PackageChecker::isGenerateMigrationsAvailable();

        $config       = (new ConfigProvider())->__invoke();
        $dependencies = $config['dependencies'];
        $commands     = $config['laminas-cli']['commands'];

        $this->assertSame(
            self::expectedCommands($console, $behavior, $migrator, $generator),
            $commands,
        );
        $this->assertSame(
            self::expectedProviderFactories($console, $behavior, $migrator, $generator),
            $dependencies['factories'],
        );

        $expectedAliases = [
            DatabaseProviderInterface::class => 'dbal',
            ORMInterface::class              => 'orm',
            SchemaCompilerInterface::class   => SchemaCompilerService::class,
        ];

        if ($migrator) {
            $expectedAliases[MigratorInterface::class] = 'migrator';
        }

        $this->assertSame($expectedAliases, $dependencies['aliases']);
        $this->assertSame(
            [
                CompiledSchemaStorage::class => CompiledSchemaStorage::class,
            ],
            $dependencies['invokables'],
        );

        $expectedAbstractFactories = PackageChecker::isServiceManagerAvailable()
            ? [
                NamedDatabaseAbstractFactory::class => NamedDatabaseAbstractFactory::class,
            ]
            : [];

        $this->assertSame($expectedAbstractFactories, $dependencies['abstract_factories']);

        foreach ($commands as $commandClass) {
            self::assertArrayHasKey($commandClass, $dependencies['factories']);
            $factoryClass = $dependencies['factories'][$commandClass];

            self::assertTrue(class_exists($commandClass), "Command {$commandClass} must autoload.");
            self::assertTrue(class_exists($factoryClass), "Factory {$factoryClass} must autoload.");
        }
    }

    public function testMigrationsLayerRegistrationsMatchAvailablePackages(): void
    {
        $console   = PackageChecker::isConsoleAvailable();
        $behavior  = PackageChecker::isEntityBehaviorAvailable();
        $generator = PackageChecker::isGenerateMigrationsAvailable();

        $layer = new MigrationsLayer();

        $expectedFactories = [
            'migrator'                     => MigratorFactory::class,
            MigratorService::class         => MigratorServiceFactory::class,
        ];

        if ($console) {
            $expectedFactories[Command\Migrator\MigrateCommand::class]
                = Command\Migrator\MigrateCommandFactory::class;
            $expectedFactories[Command\Migrator\RollbackCommand::class]
                = Command\Migrator\RollbackCommandFactory::class;
            $expectedFactories[Command\Migrator\CreateMigrationCommand::class]
                = Command\Migrator\CreateMigrationCommandFactory::class;
            $expectedFactories[Command\Migrator\CreateSeedCommand::class]
                = Command\Migrator\CreateSeedCommandFactory::class;
            $expectedFactories[Command\Migrator\SeedCommand::class]
                = Command\Migrator\SeedCommandFactory::class;

            if ($generator && $behavior) {
                $expectedFactories[Command\Cycle\SchemaMigrationsGenerateCommand::class]
                    = Command\Cycle\SchemaMigrationsGenerateCommandFactory::class;
            }
        }

        $this->assertSame(
            [
                'aliases'   => [
                    MigratorInterface::class => 'migrator',
                ],
                'factories' => $expectedFactories,
            ],
            $layer->getDependencies(),
        );

        $expectedCommands = [];

        if ($console) {
            $expectedCommands = [
                'cycle:migration:run'      => Command\Migrator\MigrateCommand::class,
                'cycle:migration:rollback' => Command\Migrator\RollbackCommand::class,
                'cycle:migration:create'   => Command\Migrator\CreateMigrationCommand::class,
                'cycle:seed:create'        => Command\Migrator\CreateSeedCommand::class,
                'cycle:seed:run'           => Command\Migrator\SeedCommand::class,
            ];

            if ($generator && $behavior) {
                $expectedCommands['cycle:schema:migration:generate']
                    = Command\Cycle\SchemaMigrationsGenerateCommand::class;
            }
        }

        $this->assertSame($expectedCommands, $layer->getCommands());
    }

    public function testCiPackageScenarioMatchesActualAvailability(): void
    {
        $scenario = getenv('CYCLE_OPTIONAL_SCENARIO');

        if (false === $scenario) {
            $this->addToAssertionCount(1);

            return;
        }

        /** @var array<string, array{console: bool, behavior: bool, migrator: bool, generator: bool, servicemanager: bool}> $scenarios */
        $scenarios = [
            'all-present'       => [
                'console'        => true,
                'behavior'       => true,
                'migrator'       => true,
                'generator'      => true,
                'servicemanager' => true,
            ],
            'no-console'        => [
                'console'        => false,
                'behavior'       => true,
                'migrator'       => true,
                'generator'      => true,
                'servicemanager' => true,
            ],
            'no-behavior'       => [
                'console'        => true,
                'behavior'       => false,
                'migrator'       => true,
                'generator'      => true,
                'servicemanager' => true,
            ],
            'no-generator'      => [
                'console'        => true,
                'behavior'       => true,
                'migrator'       => true,
                'generator'      => false,
                'servicemanager' => true,
            ],
            'no-migrations'     => [
                'console'        => true,
                'behavior'       => true,
                'migrator'       => false,
                'generator'      => false,
                'servicemanager' => true,
            ],
            'no-servicemanager' => [
                'console'        => true,
                'behavior'       => true,
                'migrator'       => true,
                'generator'      => true,
                'servicemanager' => false,
            ],
            'all-absent'        => [
                'console'        => false,
                'behavior'       => false,
                'migrator'       => false,
                'generator'      => false,
                'servicemanager' => false,
            ],
        ];

        if (! isset($scenarios[$scenario])) {
            $this->fail("Unknown CYCLE_OPTIONAL_SCENARIO: {$scenario}");
        }

        $actual = [
            'console'        => PackageChecker::isConsoleAvailable(),
            'behavior'       => PackageChecker::isEntityBehaviorAvailable(),
            'migrator'       => PackageChecker::isMigratorAvailable(),
            'generator'      => PackageChecker::isGenerateMigrationsAvailable(),
            'servicemanager' => PackageChecker::isServiceManagerAvailable(),
        ];

        $this->assertSame($scenarios[$scenario], $actual);
    }

    /**
     * @return array<string, class-string>
     */
    private static function expectedCommands(bool $console, bool $behavior, bool $migrator, bool $generator): array
    {
        if (! $console) {
            return [];
        }

        $commands = [
            'cycle:cache:clear' => Command\Cycle\ClearCycleSchemaCache::class,
        ];

        if ($behavior) {
            $commands['cycle:schema:sync']    = Command\Cycle\SchemaSyncCommand::class;
            $commands['cycle:schema:compile'] = Command\Cycle\SchemaCompileCommand::class;
        }

        if ($migrator) {
            $commands['cycle:migration:run']      = Command\Migrator\MigrateCommand::class;
            $commands['cycle:migration:rollback'] = Command\Migrator\RollbackCommand::class;
            $commands['cycle:migration:create']   = Command\Migrator\CreateMigrationCommand::class;
            $commands['cycle:seed:create']        = Command\Migrator\CreateSeedCommand::class;
            $commands['cycle:seed:run']           = Command\Migrator\SeedCommand::class;

            if ($generator && $behavior) {
                $commands['cycle:schema:migration:generate']
                    = Command\Cycle\SchemaMigrationsGenerateCommand::class;
            }
        }

        return $commands;
    }

    /**
     * @return array<string, class-string>
     */
    private static function expectedProviderFactories(bool $console, bool $behavior, bool $migrator, bool $generator): array
    {
        $factories = [
            'orm'                                => CycleFactory::class,
            'dbal'                               => DbalFactory::class,
            DatabaseInterface::class             => DatabaseFactory::class,
            SchemaCompilerService::class         => SchemaCompilerServiceFactory::class,
        ];

        if ($console) {
            $factories[Command\Cycle\ClearCycleSchemaCache::class]
                = Command\Cycle\ClearCycleSchemaCacheFactory::class;

            if ($behavior) {
                $factories[Command\Cycle\SchemaSyncCommand::class]
                    = Command\Cycle\SchemaSyncCommandFactory::class;
                $factories[Command\Cycle\SchemaCompileCommand::class]
                    = Command\Cycle\SchemaCompileCommandFactory::class;
            }
        }

        if ($migrator) {
            $factories['migrator']                = MigratorFactory::class;
            $factories[MigratorService::class]    = MigratorServiceFactory::class;

            if ($console) {
                $factories[Command\Migrator\MigrateCommand::class]
                    = Command\Migrator\MigrateCommandFactory::class;
                $factories[Command\Migrator\RollbackCommand::class]
                    = Command\Migrator\RollbackCommandFactory::class;
                $factories[Command\Migrator\CreateMigrationCommand::class]
                    = Command\Migrator\CreateMigrationCommandFactory::class;
                $factories[Command\Migrator\CreateSeedCommand::class]
                    = Command\Migrator\CreateSeedCommandFactory::class;
                $factories[Command\Migrator\SeedCommand::class]
                    = Command\Migrator\SeedCommandFactory::class;

                if ($generator && $behavior) {
                    $factories[Command\Cycle\SchemaMigrationsGenerateCommand::class]
                        = Command\Cycle\SchemaMigrationsGenerateCommandFactory::class;
                }
            }
        }

        return $factories;
    }
}
