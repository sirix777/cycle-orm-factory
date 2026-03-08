<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Cycle\Database\DatabaseManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;

use function is_array;

final class SchemaSyncCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SchemaSyncCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $schemaConfig = $config['cycle']['schema'] ?? [];
        $manualMappingSchemaDefinitions = $schemaConfig['manual_mapping_schema_definitions']
            ?? $schemaConfig['manual_entity_schema_definition']
            ?? [];
        $entities = is_array($config['cycle']['entities'] ?? null) ? $config['cycle']['entities'] : [];
        $manualMapping = is_array($manualMappingSchemaDefinitions) ? $manualMappingSchemaDefinitions : [];
        $additionalGenerators = is_array($config['cycle']['generators'] ?? null) ? $config['cycle']['generators'] : [];
        $compiledSchemaPath = $schemaConfig['compiled']['path'] ?? CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH;
        $isCacheEnabled = (bool) ($schemaConfig['cache']['enabled'] ?? true);

        /** @var SchemaCompilerInterface $schemaCompiler */
        $schemaCompiler = $container->get(SchemaCompilerInterface::class);

        /** @var CompiledSchemaStorage $compiledSchemaStorage */
        $compiledSchemaStorage = $container->get(CompiledSchemaStorage::class);

        /** @var DatabaseManager $dbal */
        $dbal = $container->get('dbal');

        return new SchemaSyncCommand(
            $schemaCompiler,
            $compiledSchemaStorage,
            $dbal,
            $entities,
            $manualMapping,
            $additionalGenerators,
            $compiledSchemaPath,
            $isCacheEnabled,
        );
    }
}
