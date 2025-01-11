<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;
use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\State;
use Mockery;
use Mockery\MockInterface;
use Throwable;

use const PHP_EOL;

class MigratorServiceTest extends TestCase
{
    private MigratorInterface|MockInterface $migratorMock;
    private MigratorService $migratorService;

    public function setUp(): void
    {
        parent::setUp();
        $this->migratorMock = Mockery::mock(MigratorInterface::class);

        /** @var MigratorInterface $migratorMock */
        $migratorMock = $this->migratorMock;
        $this->migratorService = new MigratorService($migratorMock);

        $this->migratorMock
            ->shouldReceive('isConfigured')
            ->once()
            ->andReturnFalse();
        $this->migratorMock->shouldReceive('configure')->once();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testMigrate(): void
    {
        $migrationInterfaceMock = Mockery::mock(MigrationInterface::class);

        $stateMock = Mockery::mock('overload:' . State::class);

        $stateMock
            ->shouldReceive('getName')
            ->andReturn('tests-migration');

        $migrationInterfaceMock
            ->shouldReceive('getState')
            ->once()
            ->andReturn($stateMock);

        $this->migratorMock
            ->shouldReceive('run')
            ->times(2)
            ->andReturn(
                $migrationInterfaceMock,
                null
            );

        $this->expectOutputString(
            'Migrating tests-migration' . PHP_EOL
            . 'Migrate successful' . PHP_EOL
        );
        $this->migratorService->migrate();
    }

    /**
     * @throws Throwable
     */
    public function testRollback(): void
    {
        $this->migratorMock
            ->shouldReceive('rollback')
            ->once();

        $this->expectOutputString(
            'Rollback successful' . PHP_EOL
        );
        $this->migratorService->rollback();
    }
}
