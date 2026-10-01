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

        foreach ($this->getCommandRegistrations() as $registration) {
            $factories[$registration['command']] = $registration['factory'];
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
        $commands = [];

        foreach ($this->getCommandRegistrations() as $name => $registration) {
            $commands[$name] = $registration['command'];
        }

        if (PackageChecker::isMigratorAvailable()) {
            $commands = [...$commands, ...$this->migrationsLayer->getCommands()];
        }

        return [
            'commands' => $commands,
        ];
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
            CommandName::CacheClear->value => [
                'command' => Command\Cycle\ClearCycleSchemaCache::class,
                'factory' => Command\Cycle\ClearCycleSchemaCacheFactory::class,
            ],
        ];

        if (PackageChecker::isEntityBehaviorAvailable()) {
            $registrations[CommandName::SchemaSync->value] = [
                'command' => Command\Cycle\SchemaSyncCommand::class,
                'factory' => Command\Cycle\SchemaSyncCommandFactory::class,
            ];
            $registrations[CommandName::SchemaCompile->value] = [
                'command' => Command\Cycle\SchemaCompileCommand::class,
                'factory' => Command\Cycle\SchemaCompileCommandFactory::class,
            ];
        }

        return $registrations;
    }
}
