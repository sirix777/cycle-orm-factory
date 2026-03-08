<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;

final class ClearCycleSchemaCacheFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ClearCycleSchemaCache
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $schemaConfig = $config['cycle']['schema'] ?? [];
        $enabled = (bool) ($schemaConfig['cache']['enabled'] ?? true);
        $compiledSchemaPath = $schemaConfig['compiled']['path'] ?? CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH;

        return new ClearCycleSchemaCache(
            $container->get(CompiledSchemaStorage::class),
            $compiledSchemaPath,
            $enabled,
        );
    }
}
