<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Cycle\Database\DatabaseManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;

final class SchemaMigrationsGenerateCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): SchemaMigrationsGenerateCommand
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new SchemaMigrationsGenerateCommand(
            $containerResolver->get(SchemaCompilerInterface::class),
            $containerResolver->get(CompiledSchemaStorage::class),
            $containerResolver->getAs('dbal', DatabaseManager::class),
            $configReader->nonEmptyStringList('cycle.entities', []),
            $this->resolveManualMappingSchemaDefinitions($configReader),
            $configReader->list('cycle.generators', []),
            $configReader->nonEmptyString('cycle.schema.compiled.path', CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH),
            $configReader->bool('cycle.schema.cache.enabled', true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveManualMappingSchemaDefinitions(ConfigReader $configReader): array
    {
        if ($configReader->has('cycle.schema.manual_mapping_schema_definitions')) {
            return $configReader->map('cycle.schema.manual_mapping_schema_definitions', []);
        }

        // Deprecated legacy key; remove support in 4.0.
        return $configReader->map('cycle.schema.manual_entity_schema_definition', []);
    }
}
