<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\DatabaseManager;
use Cycle\ORM;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\ORM\Transaction\CommandGeneratorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Internal\PackageChecker;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;

use function is_array;
use function is_string;

final class CycleFactory
{
    public const DEFAULT_COMPILED_SCHEMA_PATH = 'data/cache/cycle/schema.php';

    /**
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ORMInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $dbal = $container->get('dbal');
        if (! $dbal instanceof DatabaseManager) {
            throw new ConfigException('Expected dbal service');
        }
        $entities = $this->normalizeEntityDirectories($config['cycle']['entities'] ?? []);
        $schemaConfig = $config['cycle']['schema'] ?? [];
        $manualMappingSchemaDefinitions = $this->resolveManualMappingSchemaDefinitions($schemaConfig);
        $additionalGenerators = $config['cycle']['generators'] ?? [];
        $isCacheEnabled = (bool) ($schemaConfig['cache']['enabled'] ?? true);
        $compiledSchemaPath = $schemaConfig['compiled']['path'] ?? self::DEFAULT_COMPILED_SCHEMA_PATH;

        $compiledSchemaStorage = $container->has(CompiledSchemaStorage::class)
            ? $container->get(CompiledSchemaStorage::class)
            : new CompiledSchemaStorage();

        if (! $compiledSchemaStorage instanceof CompiledSchemaStorage) {
            throw new ConfigException('CompiledSchemaStorage service must be an instance of ' . CompiledSchemaStorage::class);
        }

        if ($isCacheEnabled && $compiledSchemaStorage->has($compiledSchemaPath)) {
            return $this->createOrmInstance($container, $dbal, $compiledSchemaStorage->load($compiledSchemaPath));
        }

        $schemaCompiler = $container->has(SchemaCompilerInterface::class)
            ? $container->get(SchemaCompilerInterface::class)
            : new SchemaCompilerService($container);

        if (! $schemaCompiler instanceof SchemaCompilerInterface) {
            throw new ConfigException('Schema compiler service must implement ' . SchemaCompilerInterface::class);
        }

        $schema = $schemaCompiler->compile(
            $dbal,
            $entities,
            $manualMappingSchemaDefinitions,
            $additionalGenerators,
        );

        if ($isCacheEnabled) {
            $compiledSchemaStorage->save($compiledSchemaPath, $schema);
        }

        return $this->createOrmInstance($container, $dbal, $schema);
    }

    /**
     * @param array<string, mixed> $schemaConfig
     *
     * @return array<string, mixed>
     */
    private function resolveManualMappingSchemaDefinitions(array $schemaConfig): array
    {
        $definitions = $schemaConfig['manual_mapping_schema_definitions']
            ?? $schemaConfig['manual_entity_schema_definition']
            ?? [];

        return is_array($definitions) ? $definitions : [];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeEntityDirectories(mixed $entities): array
    {
        if (! is_array($entities)) {
            return [];
        }

        $result = [];
        foreach ($entities as $entityDir) {
            if (is_string($entityDir) && '' !== $entityDir) {
                $result[] = $entityDir;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function createOrmInstance(
        ContainerInterface $container,
        DatabaseManager $dbal,
        array $schema
    ): ORMInterface {
        $schemaInstance = new ORMSchema($schema);
        $commandGenerator = $this->createCommandGenerator($schemaInstance, $container);

        return new ORM\ORM(
            factory: new ORM\Factory($dbal),
            schema: $schemaInstance,
            commandGenerator: $commandGenerator,
        );
    }

    private function createCommandGenerator(
        ORMSchema $schema,
        ContainerInterface $container
    ): ?CommandGeneratorInterface {
        if (! PackageChecker::isEntityBehaviorAvailable()) {
            return null;
        }

        return new EventDrivenCommandGenerator($schema, $container);
    }
}
