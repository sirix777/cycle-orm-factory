<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Database\DatabaseManager;
use Cycle\ORM;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\Schema;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Enum\SchemaProperty;
use Sirix\Cycle\Resolver\CacheAdapterResolver;
use Sirix\Cycle\Service\MigratorInterface;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

use function array_merge;

class CycleFactory
{
    public const DEFAULT_CACHE_KEY = 'cycle_orm_schema';

    /**
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container): ORMInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['cycle']['entities'])) {
            throw new ConfigException('Expected config entities');
        }

        $dbal = $container->get('dbal');
        $entities = $config['cycle']['entities'];
        $schemaProperty = $config['cycle']['schema']['property'] ?? null;
        $isCacheEnabled = $config['cycle']['schema']['cache']['enabled'] ?? false;
        $manualMappingSchemaDefinitions = $config['cycle']['schema']['manual_mapping_schema_definitions'] ?? [];
        $cacheKey = $config['cycle']['schema']['cache']['key'] ?? self::DEFAULT_CACHE_KEY;

        if ($isCacheEnabled) {
            $cache = (new CacheAdapterResolver())->resolve($container, $config);
            $cachedSchema = $cache->getItem($cacheKey);

            if ($cachedSchema->isHit()) {
                return $this->createOrmInstance($container, $dbal, $cachedSchema->get());
            }
        }

        $schema = $this->compileSchema($container, $entities, $dbal, $manualMappingSchemaDefinitions, $schemaProperty);

        if ($isCacheEnabled) {
            $cachedSchema->set($schema);
            $cache->save($cachedSchema);
        }

        return $this->createOrmInstance($container, $dbal, $schema);
    }

    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     *
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function compileSchema(
        ContainerInterface $container,
        array $entities,
        DatabaseManager $dbal,
        array $manualMappingSchemaDefinitions,
        ?SchemaProperty $schemaProperty,
    ): array {
        $migrator = $container->get('migrator');

        $finder = (new Finder())->files()->in($entities);
        $classLocator = new ClassLocator($finder);

        $generators = $this->getSchemaGenerators($classLocator, $migrator, $schemaProperty);

        $schemaCompiler = new Schema\Compiler();
        $schema = $schemaCompiler->compile(new Schema\Registry($dbal), $generators);

        return array_merge($schema, $manualMappingSchemaDefinitions);
    }

    /**
     * @return array<Schema\GeneratorInterface>
     */
    private function getSchemaGenerators(
        ClassLocator $classLocator,
        MigratorInterface $migrator,
        ?SchemaProperty $schemaProperty
    ): array {
        $embeddingLocator = new TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new TokenizerEntityLocator($classLocator);

        $generators = [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($embeddingLocator),
            new Annotated\Entities($entityLocator),
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\GenerateModifiers(),
            new Schema\Generator\ValidateEntities(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\RenderRelations(),
            new Schema\Generator\RenderModifiers(),
            new Schema\Generator\ForeignKeys(),
            new Annotated\MergeIndexes(),
            new Schema\Generator\GenerateTypecast(),
        ];

        if (SchemaProperty::SyncTables === $schemaProperty) {
            $generators[] = new Schema\Generator\SyncTables();
        }

        if (SchemaProperty::GenerateMigrations === $schemaProperty) {
            $generators[] = new GenerateMigrations(
                $migrator->getRepository(),
                $migrator->getConfig()
            );
        }

        return $generators;
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
        $commandGenerator = new EventDrivenCommandGenerator($schemaInstance, $container);

        return new ORM\ORM(
            factory: new ORM\Factory($dbal),
            schema: $schemaInstance,
            commandGenerator: $commandGenerator,
        );
    }
}
