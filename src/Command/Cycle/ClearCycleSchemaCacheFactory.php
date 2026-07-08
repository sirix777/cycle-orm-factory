<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;

final class ClearCycleSchemaCacheFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ClearCycleSchemaCache
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new ClearCycleSchemaCache(
            $containerResolver->get(CompiledSchemaStorage::class),
            $configReader->nonEmptyString('cycle.schema.compiled.path', CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH),
            $configReader->bool('cycle.schema.cache.enabled', true),
        );
    }
}
