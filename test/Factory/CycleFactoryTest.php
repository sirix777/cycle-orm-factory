<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\ORM;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\MigratorInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;

use function file_exists;

class CycleFactoryTest extends TestCase
{
    private const DEFAULT_CACHE_DIR = 'tests/cache';
    private ContainerInterface|MockObject $container;
    private CacheItemPoolInterface $cache;

    /** @var array<string, array<int|string, mixed>> */
    private array $config;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(
            ContainerInterface::class
        );
        $this->config = [
            'cycle' => [
                'entities' => [
                    'src',
                ],
                'schema' => [
                    'property' => null,
                ],
            ],
        ];

        $this->cache = new FilesystemAdapter(
            'cycle_orm_test',
            0,
            self::DEFAULT_CACHE_DIR
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if (file_exists(self::DEFAULT_CACHE_DIR)) {
            $this->removeCacheDir();
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException|NotFoundExceptionInterface
     */
    public function testFactoryWithoutConfig(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false)
        ;

        $factory = new CycleFactory();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config entities');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception|InvalidArgumentException
     */
    public function testFactoryWithConfigWithoutDbal(): void
    {
        $exceptionMock = $this->createMock(
            NotFoundExceptionInterface::class
        );

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true)
        ;

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn ($serviceName) => match ($serviceName) {
                'config' => $this->config,
                'cache' => $this->cache,
                'dbal' => throw new $exceptionMock('dbal not found'),
                default => null,
            })
        ;

        $factory = new CycleFactory();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('dbal not found');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception|InvalidArgumentException
     */
    public function testFactoryWithConfigWithDbalWithoutMigrator(): void
    {
        $exceptionMock = $this->createMock(
            NotFoundExceptionInterface::class
        );

        $this->container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(fn ($serviceName) => match ($serviceName) {
                'config' => $this->config,
                'cache' => $this->cache,
                'dbal' => new DatabaseManager(new DatabaseConfig([])),
                'migrator' => throw new $exceptionMock('migrator not found'),
                default => null,
            })
        ;

        $factory = new CycleFactory();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('migrator not found');
        $factory($this->container);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public function testFactoryWithConfigWithDbalWithMigratorWithCache(): void
    {
        $this->makeFactory(true);
        $this->assertTrue(file_exists(self::DEFAULT_CACHE_DIR));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public function testFactoryWithConfigWithDbalWithMigratorWithoutCache(): void
    {
        $this->makeFactory();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception|InvalidArgumentException
     */
    private function makeFactory(bool $cache = false): void
    {
        $migratorMock = $this->createMock(MigratorInterface::class);

        $config = $this->config;
        if ($cache) {
            $config['cycle']['schema']['cache']['enabled'] = true;
        }

        $this->container
            ->method('has')
            ->willReturnCallback(fn ($serviceName) => match ($serviceName) {
                'config' => true,
                'cache' => $cache,
                default => false,
            })
        ;

        $this->container
            ->expects($this->exactly($cache ? 4 : 3))
            ->method('get')
            ->willReturnCallback(fn ($serviceName) => match ($serviceName) {
                'config' => $config,
                'dbal' => new DatabaseManager(new DatabaseConfig([])),
                'migrator' => $migratorMock,
                'cache' => $cache ? $this->cache : null,
                default => null,
            })
        ;

        $factory = new CycleFactory();

        $this->assertInstanceOf(
            ORM::class,
            $factory($this->container)
        );
    }

    private function removeCacheDir(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(self::DEFAULT_CACHE_DIR);
    }
}
