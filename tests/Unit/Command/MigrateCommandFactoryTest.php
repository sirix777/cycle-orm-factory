<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Unit\Command;

use Codeception\PHPUnit\TestCase;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Command\Migrator\MigrateCommand;
use Sirix\Cycle\Command\Migrator\MigrateCommandFactory;
use Sirix\Cycle\Service\MigratorService;

class MigrateCommandFactoryTest extends TestCase
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
     * @throws Exception
     */
    public function testFactoryWithoutMigratorService(): void
    {
        $exceptionMock = $this->createMock(
            NotFoundExceptionInterface::class
        );
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(MigratorService::class)
            ->willThrowException(
                new $exceptionMock('migrator service not found')
            );

        $factory = new MigrateCommandFactory();
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('migrator service not found');
        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithMigratorService(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(MigratorService::class)
            ->willReturn(
                $this->createMock(MigratorService::class)
            );

        $factory        = new MigrateCommandFactory();
        $migrateCommand = $factory($this->container);
        $this->assertInstanceOf(
            MigrateCommand::class,
            $migrateCommand
        );
    }
}
