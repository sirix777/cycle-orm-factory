<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Factory\DbalFactory;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class DbalFactoryTest extends TestCase
{
    private MockObject|ContainerInterface $container;

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
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);
        $factory = new DbalFactory();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config databases');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithConfig(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(['cycle' => []]);

        $factory = new DbalFactory();
        $this->assertInstanceOf(
            DatabaseProviderInterface::class,
            $factory($this->container)
        );
    }
}
