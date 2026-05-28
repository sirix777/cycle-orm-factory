<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\DatabaseProviderInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\Exception\MissingConfigValueException;
use Sirix\Cycle\Factory\DbalFactory;

final class DbalFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(
            ContainerInterface::class
        );
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
        $factory = new DbalFactory();
        $this->expectException(MissingConfigValueException::class);
        $this->expectExceptionMessage('cycle.db-config');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithConfig(): void
    {
        $this->container
            ->method('has')
            ->willReturnCallback(static fn (string $id): bool => 'config' === $id)
        ;

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'cycle' => [
                        'db-config' => [],
                    ],
                ]
            )
        ;

        $factory = new DbalFactory();
        $this->assertInstanceOf(
            DatabaseProviderInterface::class,
            $factory($this->container)
        );
    }
}
