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
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Internal\PackageChecker;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;

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
        try {
            return $this->createOrm($container);
        } catch (ResolverException $exception) {
            throw new ConfigException($exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    private function createOrm(ContainerInterface $container): ORMInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader = ConfigReader::fromContainer($containerResolver);

        $databaseManager = $containerResolver->getAs('dbal', DatabaseManager::class);
        $entities = $configReader->nonEmptyStringList('cycle.entities', []);
        $manualMappingSchemaDefinitions = $this->resolveManualMappingSchemaDefinitions($configReader);
        $additionalGenerators = $configReader->list('cycle.generators', []);
        $isCacheEnabled = $configReader->bool('cycle.schema.cache.enabled', true);
        $compiledSchemaPath = $configReader->nonEmptyString(
            'cycle.schema.compiled.path',
            self::DEFAULT_COMPILED_SCHEMA_PATH,
        );

        $compiledSchemaStorage = $containerResolver->has(CompiledSchemaStorage::class)
            ? $containerResolver->get(CompiledSchemaStorage::class)
            : new CompiledSchemaStorage();

        if ($isCacheEnabled && $compiledSchemaStorage->has($compiledSchemaPath)) {
            return $this->createOrmInstance($container, $databaseManager, $compiledSchemaStorage->load($compiledSchemaPath));
        }

        $schemaCompiler = $containerResolver->has(SchemaCompilerInterface::class)
            ? $containerResolver->get(SchemaCompilerInterface::class)
            : new SchemaCompilerService($container);

        $schema = $schemaCompiler->compile(
            $databaseManager,
            $entities,
            $manualMappingSchemaDefinitions,
            $additionalGenerators,
        );

        if ($isCacheEnabled) {
            $compiledSchemaStorage->save($compiledSchemaPath, $schema);
        }

        return $this->createOrmInstance($container, $databaseManager, $schema);
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

    /**
     * @param array<string, mixed> $schema
     */
    private function createOrmInstance(
        ContainerInterface $container,
        DatabaseManager $databaseManager,
        array $schema
    ): ORMInterface {
        $schemaInstance = new ORMSchema($schema);
        $commandGenerator = $this->createCommandGenerator($schemaInstance, $container);

        return new ORM\ORM(
            factory: new ORM\Factory($databaseManager),
            schema: $schemaInstance,
            commandGenerator: $commandGenerator,
        );
    }

    private function createCommandGenerator(
        ORMSchema $ormSchema,
        ContainerInterface $container
    ): ?CommandGeneratorInterface {
        if (! PackageChecker::isEntityBehaviorAvailable()) {
            return null;
        }

        return new EventDrivenCommandGenerator($ormSchema, $container);
    }
}
