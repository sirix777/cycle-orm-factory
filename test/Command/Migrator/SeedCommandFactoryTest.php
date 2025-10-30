<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Exception\ConfigException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\Command\Migrator\SeedCommandFactory;
use Sirix\Cycle\Service\MigratorService;

class SeedCommandFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private MigratorService|MockObject $migratorService;
    private DatabaseProviderInterface|MockObject $dbal;

    public function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->migratorService = $this->createMock(MigratorService::class);
        $this->dbal = $this->createMock(DatabaseProviderInterface::class);
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

        $factory = new SeedCommandFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config migrator with seed_directory');

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
                        // seed_directory is missing
                    ],
                ],
            ])
        ;

        $factory = new SeedCommandFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected config migrator with seed_directory');

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
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['config', [
                    'cycle' => [
                        'migrator' => [
                            'seed_directory' => 'test/seeds',
                        ],
                    ],
                ]],
                [MigratorService::class, $this->migratorService],
                ['dbal', $this->dbal],
            ])
        ;

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true)
        ;

        $factory = new SeedCommandFactory();
        $command = $factory($this->container);

        $this->assertInstanceOf(
            SeedCommand::class,
            $command
        );
    }
}
