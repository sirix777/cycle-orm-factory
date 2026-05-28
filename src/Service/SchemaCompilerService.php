<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Cycle\Schema;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;
use Cycle\Schema\GeneratorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Enum\SchemaCompileMode;
use Sirix\Cycle\Internal\MigrationsToggle;
use Sirix\Cycle\Internal\PackageChecker;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

use function array_merge;
use function class_exists;
use function is_string;
use function sprintf;

final readonly class SchemaCompilerService implements SchemaCompilerInterface
{
    public function __construct(private ContainerInterface $container) {}

    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     * @param array<int, mixed>    $additionalGenerators
     *
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compile(
        DatabaseManager $databaseManager,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
        SchemaCompileMode $schemaCompileMode = SchemaCompileMode::Runtime,
    ): array {
        return $this->compileInternal(
            $databaseManager,
            $entities,
            $manualMappingSchemaDefinitions,
            $additionalGenerators,
            $schemaCompileMode,
        );
    }

    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     * @param array<int, mixed>    $additionalGenerators
     *
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function compileInternal(
        DatabaseManager $databaseManager,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators,
        SchemaCompileMode $schemaCompileMode,
    ): array {
        $entities = $this->normalizeEntityDirectories($entities);

        if ([] === $entities && [] === $manualMappingSchemaDefinitions && [] === $additionalGenerators) {
            throw new ConfigException(
                <<<'MESSAGE'
                    Expected at least one entity directory in config.cycle.entities
                    or manual schema definitions in config.cycle.schema.manual_mapping_schema_definitions
                    or at least one schema generator in config.cycle.generators
                    MESSAGE
            );
        }

        /** @var array<GeneratorInterface> $generators */
        $generators = [
            new Schema\Generator\ResetTables(),
        ];

        $generators = [
            ...$generators,
            ...$this->resolveAdditionalGenerators($additionalGenerators),
        ];

        if ([] !== $entities) {
            $finder = (new Finder())->files()->in($entities);
            $classLocator = new ClassLocator($finder);

            $generators = [
                ...$generators,
                new Annotated\Embeddings(new TokenizerEmbeddingLocator($classLocator)),
                new Annotated\Entities(new TokenizerEntityLocator($classLocator)),
            ];
        }

        $generators = [
            ...$generators,
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

        $modeGenerator = match ($schemaCompileMode) {
            SchemaCompileMode::Runtime => null,
            SchemaCompileMode::SyncTables => new Schema\Generator\SyncTables(),
            SchemaCompileMode::GenerateMigrations => $this->createGenerateMigrationsGenerator(),
        };

        if ($modeGenerator instanceof GeneratorInterface) {
            $generators[] = $modeGenerator;
        }

        $schemaCompiler = new Schema\Compiler();
        $schema = $schemaCompiler->compile(
            new Schema\Registry($databaseManager),
            $generators,
        );

        return array_merge($schema, $manualMappingSchemaDefinitions);
    }

    /**
     * @param array<int, mixed> $entities
     *
     * @return array<int, string>
     */
    private function normalizeEntityDirectories(array $entities): array
    {
        $result = [];

        foreach ($entities as $entity) {
            if (! is_string($entity)) {
                continue;
            }

            if ('' === $entity) {
                continue;
            }

            $result[] = $entity;
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $additionalGenerators
     *
     * @return array<GeneratorInterface>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveAdditionalGenerators(array $additionalGenerators): array
    {
        $resolved = [];

        foreach ($additionalGenerators as $additionalGenerator) {
            $instance = match (true) {
                $additionalGenerator instanceof GeneratorInterface => $additionalGenerator,
                is_string($additionalGenerator) && $this->container->has($additionalGenerator) => $this->container->get($additionalGenerator),
                is_string($additionalGenerator) && class_exists($additionalGenerator) => new $additionalGenerator(),
                default => throw new ConfigException('Invalid schema generator provided in config.cycle.generators')
            };

            if (! $instance instanceof GeneratorInterface) {
                throw new ConfigException('Invalid schema generator provided in config.cycle.generators');
            }

            $resolved[] = $instance;
        }

        return $resolved;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createGenerateMigrationsGenerator(): GenerateMigrations
    {
        if (! PackageChecker::isGenerateMigrationsAvailable() || MigrationsToggle::isDisabledByEnv()) {
            throw new ConfigException(
                <<<'MESSAGE'
                    Schema migrations generator is unavailable. Ensure cycle/migrations and
                    cycle/schema-migrations-generator are installed and CYCLE_MIGRATIONS_DISABLED is not enabled.
                    MESSAGE
            );
        }

        $migrator = $this->container->get('migrator');
        if (! $migrator instanceof MigratorInterface) {
            throw new ConfigException(sprintf('Service "migrator" must implement %s', MigratorInterface::class));
        }

        return new GenerateMigrations($migrator->getRepository(), $migrator->getConfig());
    }
}
