<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use Mockery;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\Exception\MissingContainerServiceException;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\MigratorServiceFactory;
use Sirix\Cycle\Service\MigratorWrapper;

final class MigratorServiceFactoryTest extends TestCase
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

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryWithoutMigrator(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('migrator')
            ->willReturn(false)
        ;

        $factory = new MigratorServiceFactory();
        $this->expectException(MissingContainerServiceException::class);
        $this->expectExceptionMessage('migrator');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithMigrator(): void
    {
        $migratorWrapperMock = Mockery::mock(MigratorWrapper::class);

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('migrator')
            ->willReturn(true)
        ;

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('migrator')
            ->willReturn($migratorWrapperMock)
        ;
        $factory = new MigratorServiceFactory();

        $this->assertInstanceOf(
            MigratorService::class,
            $factory($this->container)
        );
    }
}
