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
        DatabaseManager $dbal,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
        SchemaCompileMode $mode = SchemaCompileMode::Runtime,
    ): array {
        return $this->compileInternal(
            $dbal,
            $entities,
            $manualMappingSchemaDefinitions,
            $additionalGenerators,
            $mode,
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
        DatabaseManager $dbal,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators,
        SchemaCompileMode $mode,
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

        $generators = [];
        if ([] !== $entities) {
            $finder = (new Finder())->files()->in($entities);
            $classLocator = new ClassLocator($finder);
            $generators = [
                new Schema\Generator\ResetTables(),
                new Annotated\Embeddings(new TokenizerEmbeddingLocator($classLocator)),
                new Annotated\Entities(new TokenizerEntityLocator($classLocator)),
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
        }

        $modeGenerators = match ($mode) {
            SchemaCompileMode::Runtime => [],
            SchemaCompileMode::SyncTables => [new Schema\Generator\SyncTables()],
            SchemaCompileMode::GenerateMigrations => [$this->createGenerateMigrationsGenerator()],
        };

        $generators = array_merge($generators, $modeGenerators);

        foreach ($this->resolveAdditionalGenerators($additionalGenerators) as $additionalGenerator) {
            $generators[] = $additionalGenerator;
        }

        $schemaCompiler = new Schema\Compiler();
        $schema = $schemaCompiler->compile(new Schema\Registry($dbal), $generators);

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

        foreach ($entities as $entityDir) {
            if (! is_string($entityDir)) {
                continue;
            }

            if ('' === $entityDir) {
                continue;
            }

            $result[] = $entityDir;
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

        foreach ($additionalGenerators as $generator) {
            $instance = match (true) {
                $generator instanceof GeneratorInterface => $generator,
                is_string($generator) && $this->container->has($generator) => $this->container->get($generator),
                is_string($generator) && class_exists($generator) => new $generator(),
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
