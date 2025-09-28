<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Resolver;

use Cycle\ORM\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Resolver\CacheAdapterResolver;
use stdClass;

final class CacheAdapterResolverTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private CacheItemPoolInterface|MockObject $cacheService;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->cacheService = $this->createMock(CacheItemPoolInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testResolveWithConfiguredCacheService(): void
    {
        $config = [
            'cycle' => [
                'schema' => [
                    'cache' => [
                        'service' => 'custom_cache_service',
                    ],
                ],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('custom_cache_service')
            ->willReturn($this->cacheService)
        ;

        $resolver = new CacheAdapterResolver();
        $resolvedCache = $resolver->resolve($this->container, $config);

        $this->assertSame($this->cacheService, $resolvedCache);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testResolveWithInvalidConfiguredCacheService(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('must implement Psr\Cache\CacheItemPoolInterface');

        $config = [
            'cycle' => [
                'schema' => [
                    'cache' => [
                        'service' => 'invalid_cache_service',
                    ],
                ],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('invalid_cache_service')
            ->willReturn(new stdClass())
        ;

        $resolver = new CacheAdapterResolver();
        $resolver->resolve($this->container, $config);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testResolveWithDefaultCacheService(): void
    {
        $config = [];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('cache')
            ->willReturn(true)
        ;
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('cache')
            ->willReturn($this->cacheService)
        ;

        $resolver = new CacheAdapterResolver();
        $resolvedCache = $resolver->resolve($this->container, $config);

        $this->assertSame($this->cacheService, $resolvedCache);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testResolveWithInvalidDefaultCacheService(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Cache service must implement Psr\Cache\CacheItemPoolInterface');

        $config = [];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('cache')
            ->willReturn(true)
        ;
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('cache')
            ->willReturn(new stdClass())
        ;

        $resolver = new CacheAdapterResolver();
        $resolver->resolve($this->container, $config);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testResolveThrowsExceptionWhenNoCacheServiceConfigured(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No PSR-6 cache implementation found');

        $config = [];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('cache')
            ->willReturn(false)
        ;

        $resolver = new CacheAdapterResolver();
        $resolver->resolve($this->container, $config);
    }
}
