<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Resolver\CacheAdapterResolver;
use Symfony\Component\Console\Command\Command;

class ClearCycleSchemaCacheFactory extends Command
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ClearCycleSchemaCache
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $cacheKey = $config['cycle']['schema']['cache']['key'] ?? CycleFactory::DEFAULT_CACHE_KEY;

        $cache = (new CacheAdapterResolver())->resolve($container, $config);

        return new ClearCycleSchemaCache($cache, $cacheKey);
    }
}
