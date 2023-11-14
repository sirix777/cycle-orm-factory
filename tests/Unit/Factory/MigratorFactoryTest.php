<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Unit\Factory;

use Codeception\PHPUnit\TestCase;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Service\MigratorInterface;

class MigratorFactoryTest extends TestCase
{
    private MockObject|ContainerInterface $container;
    /** @var array<string, array<string, string>> */
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
        $this->config    = [
            'migrator' => [
                'directory' => 'db/migrations',
                'table'     => 'migrations',
            ],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithoutConfig(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $factory = new MigratorFactory();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config migrator');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithConfigAndWithoutDbal(): void
    {
        $exceptionMock = $this->createMock(
            NotFoundExceptionInterface::class
        );

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn($serviceName) => match ($serviceName) {
                'config' => $this->config,
                'dbal' => throw new $exceptionMock('dbal not found'),
                default => null,
            });

        $factory = new MigratorFactory();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('dbal not found');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithConfigAndDbal(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['config', $this->config],
                    ['dbal', new DatabaseManager(new DatabaseConfig([]))],
                ]
            );

        $factory = new MigratorFactory();

        $this->assertInstanceOf(
            MigratorInterface::class,
            $factory($this->container)
        );
    }
}
