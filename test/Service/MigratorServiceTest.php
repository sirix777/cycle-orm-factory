<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use const PHP_EOL;

use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\State;
use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\SeedInterface;

final class MigratorServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MigratorInterface|MockInterface $migratorMock;
    private MigratorService $migratorService;

    public function setUp(): void
    {
        parent::setUp();
        $this->migratorMock = Mockery::mock(MigratorInterface::class);

        /** @var MigratorInterface $migratorMock */
        $migratorMock          = $this->migratorMock;
        $this->migratorService = new MigratorService($migratorMock);
    }

    public function testMigrate(): void
    {
        $this->expectConfigure(true);
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

    public function testRollbackConfiguresMigratorWhenNeeded(): void
    {
        $this->expectConfigure(true);
        $this->migratorMock->shouldReceive('rollback')->once();

        $this->migratorService->rollback();
    }

    public function testRollbackSkipsConfigureWhenAlreadyConfigured(): void
    {
        $this->expectConfigure(false);
        $this->migratorMock->shouldReceive('rollback')->once();

        $this->migratorService->rollback();
    }

    public function testSeedRunsSeed(): void
    {
        $seed = Mockery::mock(SeedInterface::class);
        $seed->shouldReceive('run')->once();

        /** @var SeedInterface $seed */
        $this->migratorService->seed($seed);
    }

    private function expectConfigure(bool $needed): void
    {
        $this->migratorMock
            ->shouldReceive('isConfigured')
            ->once()
            ->andReturn(! $needed)
        ;
        $this->migratorMock->shouldReceive('configure')->times($needed ? 1 : 0);
    }
}
