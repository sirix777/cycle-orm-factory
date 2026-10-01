<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Cycle\Database\DatabaseManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommand;
use Sirix\Cycle\Command\Cycle\SchemaMigrationsGenerateCommand;
use Sirix\Cycle\Command\Cycle\SchemaSyncCommand;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;

/**
 * @internal
 */
final class SchemaCommandFactory
{
    /**
     * @template T of SchemaCompileCommand|SchemaSyncCommand|SchemaMigrationsGenerateCommand
     *
     * @param class-string    $factoryClass
     * @param class-string<T> $commandClass
     *
     * @return T
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public static function create(
        ContainerInterface $container,
        string $factoryClass,
        string $commandClass,
    ): SchemaCompileCommand|SchemaMigrationsGenerateCommand|SchemaSyncCommand {
        $containerResolver = ContainerResolver::forFactory($container, $factoryClass);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new $commandClass(
            $containerResolver->get(SchemaCompilerInterface::class),
            $containerResolver->get(CompiledSchemaStorage::class),
            $containerResolver->getAs('dbal', DatabaseManager::class),
            $configReader->nonEmptyStringList('cycle.entities', []),
            $configReader->map('cycle.schema.manual_mapping_schema_definitions', []),
            $configReader->list('cycle.generators', []),
            $configReader->nonEmptyString('cycle.schema.compiled.path', CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH),
            $configReader->bool('cycle.schema.cache.enabled', true),
        );
    }
}
