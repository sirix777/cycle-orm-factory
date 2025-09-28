<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use const PHP_EOL;

use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\State;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;

final class MigratorServiceTest extends TestCase
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
            ->andReturnFalse()
        ;
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

        $state = new State(
            'tests-migration',
            new DateTime(),
            State::STATUS_PENDING
        );

        $migrationInterfaceMock
            ->shouldReceive('getState')
            ->once()
            ->andReturn($state)
        ;

        $this->migratorMock
            ->shouldReceive('run')
            ->times(2)
            ->andReturn(
                $migrationInterfaceMock,
                null
            )
        ;

        $this->expectOutputString('Migrating tests-migration' . PHP_EOL);
        $this->migratorService->migrate(function($message) {
            echo $message . PHP_EOL;
        });
    }
}
