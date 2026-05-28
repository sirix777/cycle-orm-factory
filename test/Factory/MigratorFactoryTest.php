<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\Exception\MissingConfigValueException;
use Sirix\ContainerResolver\Exception\MissingContainerServiceException;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Service\MigratorInterface;

final class MigratorFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->config = [
            'cycle' => [
                'migrator' => [
                    'directory' => 'db/migrations',
                    'table' => 'migrations',
                ],
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
            ->method('has')
            ->with('config')
            ->willReturn(false)
        ;

        $factory = new MigratorFactory();
        $this->expectException(MissingConfigValueException::class);
        $this->expectExceptionMessage('cycle.migrator');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithConfigAndWithoutDbal(): void
    {
        $this->container
            ->method('has')
            ->willReturnCallback(static fn (string $id): bool => 'config' === $id)
        ;

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($this->config)
        ;

        $factory = new MigratorFactory();

        $this->expectException(MissingContainerServiceException::class);
        $this->expectExceptionMessage('dbal');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithConfigAndDbal(): void
    {
        $this->container
            ->method('has')
            ->willReturnCallback(static fn (string $id): bool => 'config' === $id || 'dbal' === $id)
        ;

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['config', $this->config],
                    ['dbal', new DatabaseManager(new DatabaseConfig([]))],
                ]
            )
        ;

        $factory = new MigratorFactory();

        $this->assertInstanceOf(
            MigratorInterface::class,
            $factory($this->container)
        );
    }
}
