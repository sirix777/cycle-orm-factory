<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Unit\Factory;

use Codeception\PHPUnit\TestCase;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Factory\DbalFactory;

/**
 * @internal
 *
 * @coversNothing
 */
class DbalFactoryTest extends TestCase
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
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false)
        ;
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
            ->willReturn(true)
        ;

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(['cycle' => []])
        ;

        $factory = new DbalFactory();
        $this->assertInstanceOf(
            DatabaseProviderInterface::class,
            $factory($this->container)
        );
    }
}
