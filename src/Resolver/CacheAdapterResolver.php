<?php

declare(strict_types=1);

namespace Sirix\Cycle\Resolver;

use Cycle\ORM\Exception\ConfigException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function sprintf;

class CacheAdapterResolver
{
    /**
     * Resolves cache adapter from container or configuration.
     *
     * @param array<string, mixed> $config
     *
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(ContainerInterface $container, array $config): CacheItemPoolInterface
    {
        if (isset($config['cycle']['schema']['cache']['service'])) {
            $cacheService = $container->get($config['cycle']['schema']['cache']['service']);
            if ($cacheService instanceof CacheItemPoolInterface) {
                return $cacheService;
            }

            throw new ConfigException(
                sprintf(
                    'Cache service "%s" must implement Psr\Cache\CacheItemPoolInterface',
                    $config['cycle']['schema']['cache']['service']
                )
            );
        }

        if ($container->has('cache')) {
            $cache = $container->get('cache');
            if ($cache instanceof CacheItemPoolInterface) {
                return $cache;
            }

            throw new ConfigException('Cache service must implement Psr\Cache\CacheItemPoolInterface');
        }

        throw new ConfigException(
            'No PSR-6 cache implementation found. Please configure a cache service '
            . 'that implements Psr\Cache\CacheItemPoolInterface'
        );
    }
}
