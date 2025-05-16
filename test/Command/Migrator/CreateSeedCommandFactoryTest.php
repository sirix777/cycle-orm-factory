<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use Cycle\Database\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Command\Migrator\CreateSeedCommand;
use Sirix\Cycle\Command\Migrator\CreateSeedCommandFactory;

class CreateSeedCommandFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;

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
     * @throws Exception
     */
    public function testFactoryWithoutConfig(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false)
        ;

        $factory = new CreateSeedCommandFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config migrator with seed-directory');

        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithConfigWithoutSeedDirectory(): void
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
            ->willReturn([
                'cycle' => [
                    'migrator' => [
                        // seed-directory is missing
                    ],
                ],
            ])
        ;

        $factory = new CreateSeedCommandFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config migrator with seed-directory');

        $factory($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testFactoryWithConfigWithSeedDirectory(): void
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
            ->willReturn([
                'cycle' => [
                    'migrator' => [
                        'seed-directory' => 'test/seeds',
                    ],
                ],
            ])
        ;

        $factory = new CreateSeedCommandFactory();
        $command = $factory($this->container);

        $this->assertInstanceOf(
            CreateSeedCommand::class,
            $command
        );
    }
}
