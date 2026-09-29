<?php

declare(strict_types=1);

namespace Sirix\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\ORMInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DatabaseFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\NamedDatabaseAbstractFactory;
use Sirix\Cycle\Internal\MigrationsLayer;
use Sirix\Cycle\Internal\PackageChecker;
use Sirix\Cycle\Service\SchemaCompilerInterface;

final readonly class ConfigProvider
{
    public function __construct(private MigrationsLayer $migrationsLayer = new MigrationsLayer()) {}

    /**
     * @return array{
     *     dependencies: array{
     *         aliases: array<string, string>,
     *         invokables: array<string, class-string>,
     *         factories: array<string, class-string<callable(ContainerInterface, string, null|array<mixed>): mixed&object>>,
     *         abstract_factories: array<class-string<AbstractFactoryInterface>>
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
            'laminas-cli'  => $this->getCliConfig(),
        ];
    }

    /**
     * @return array{
     *     aliases: array<string, string>,
     *     invokables: array<string, class-string>,
     *     factories: array<string, class-string<callable(ContainerInterface, string, null|array<mixed>): mixed&object>>,
     *     abstract_factories: array<class-string<AbstractFactoryInterface>>
     * }
     */
    public function getDependencies(): array
    {
        $factories = [
            'orm'                                => CycleFactory::class,
            'dbal'                               => DbalFactory::class,
            DatabaseInterface::class             => DatabaseFactory::class,
            Service\SchemaCompilerService::class => Service\SchemaCompilerServiceFactory::class,
        ];

        $aliases = [
            DatabaseProviderInterface::class => 'dbal',
            ORMInterface::class              => 'orm',
            SchemaCompilerInterface::class   => Service\SchemaCompilerService::class,
        ];

        $abstractFactories = [];

        if (PackageChecker::isServiceManagerAvailable()) {
            $abstractFactories[NamedDatabaseAbstractFactory::class] = NamedDatabaseAbstractFactory::class;
        }

        if (PackageChecker::isConsoleAvailable()) {
            $factories[Command\Cycle\ClearCycleSchemaCache::class] = Command\Cycle\ClearCycleSchemaCacheFactory::class;

            if (PackageChecker::isEntityBehaviorAvailable()) {
                $factories[Command\Cycle\SchemaSyncCommand::class]    = Command\Cycle\SchemaSyncCommandFactory::class;
                $factories[Command\Cycle\SchemaCompileCommand::class] = Command\Cycle\SchemaCompileCommandFactory::class;
            }
        }

        if (PackageChecker::isMigratorAvailable()) {
            $migrationsLayerDependencies = $this->migrationsLayer->getDependencies();
            $aliases                     = [...$aliases, ...$migrationsLayerDependencies['aliases']];
            $factories                   = [...$factories, ...$migrationsLayerDependencies['factories']];
        }

        return [
            'aliases'            => $aliases,
            'invokables'         => [
                Service\CompiledSchemaStorage::class => Service\CompiledSchemaStorage::class,
            ],
            'factories'          => $factories,
            'abstract_factories' => $abstractFactories,
        ];
    }

    /**
     * @return array{commands: array<string, class-string>}
     */
    private function getCliConfig(): array
    {
        if (! PackageChecker::isConsoleAvailable()) {
            return [
                'commands' => [],
            ];
        }

        $commands = [
            CommandName::CacheClear->value => Command\Cycle\ClearCycleSchemaCache::class,
        ];

        if (PackageChecker::isEntityBehaviorAvailable()) {
            $commands[CommandName::SchemaSync->value]    = Command\Cycle\SchemaSyncCommand::class;
            $commands[CommandName::SchemaCompile->value] = Command\Cycle\SchemaCompileCommand::class;
        }

        if (PackageChecker::isMigratorAvailable()) {
            $commands = [...$commands, ...$this->migrationsLayer->getCommands()];
        }

        return [
            'commands' => $commands,
        ];
    }
}
