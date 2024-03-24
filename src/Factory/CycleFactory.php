<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Annotated;
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
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Finder\Finder;

class CycleFactory
{
    private const DEFAULT_CACHE_DIRECTORY = 'data/cache';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container): ORMInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['entities'])) {
            throw new ConfigException('Expected config entities');
        }

        $dbal = $container->get('dbal');

        $entities       = $config['entities'];
        $schemaProperty = $config['schema']['property'] ?? null;
        $isCached       = $config['schema']['cache'] ?? true;
        $cacheDirectory = $config['schema']['directory'] ??
            self::DEFAULT_CACHE_DIRECTORY;

        $cache = $this->getCacheStorage($cacheDirectory);

        $cachedSchema = $cache->getItem('schema');

        if ($cachedSchema->isHit()) {
            if ($isCached) {
                $schemaInstance   = new ORMSchema($cachedSchema->get());
                $commandGenerator = new EventDrivenCommandGenerator($schemaInstance, $container);

                return new ORM\ORM(
                    factory: new ORM\Factory($dbal),
                    schema: $schemaInstance,
                    commandGenerator: $commandGenerator
                );
            }

            $cache->deleteItem('schema');
        }

        $migrator = $container->get('migrator');

        $finder       = (new Finder())->files()->in($entities);
        $classLocator = new ClassLocator($finder);

        $generators = [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($classLocator),
            new Annotated\Entities($classLocator),
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\GenerateModifiers(),
            new Schema\Generator\ValidateEntities(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\RenderRelations(),
            new Schema\Generator\RenderModifiers(),
            new Annotated\MergeIndexes(),
            new Schema\Generator\ForeignKeys(),
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

        $schema = (new Schema\Compiler())->compile(
            new Schema\Registry($dbal),
            $generators
        );

        if ($isCached) {
            $cachedSchema->set($schema);
            $cache->save($cachedSchema);
        }

        $schemaInstance   = new ORMSchema($schema);
        $commandGenerator = new EventDrivenCommandGenerator($schemaInstance, $container);

        return new ORM\ORM(
            factory: new ORM\Factory($dbal),
            schema: $schemaInstance,
            commandGenerator: $commandGenerator
        );
    }

    private function getCacheStorage(string $directory): FilesystemAdapter
    {
        return new FilesystemAdapter(
            'cycle',
            0,
            $directory
        );
    }
}
