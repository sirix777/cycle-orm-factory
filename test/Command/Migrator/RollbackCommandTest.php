<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Command\Migrator\RollbackCommand;
use Sirix\Cycle\Service\MigratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RollbackCommandTest extends TestCase
{
    public function testRollbackSucceeds(): void
    {
        $migratorService = $this->createMock(MigratorService::class);
        $migratorService->expects($this->once())->method('rollback');

        $tester = new CommandTester(new RollbackCommand($migratorService));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('Migration rollback successful', $tester->getDisplay());
    }

    public function testRollbackFailureReturnsFailureWithMessage(): void
    {
        $migratorService = $this->createMock(MigratorService::class);
        $migratorService
            ->expects($this->once())
            ->method('rollback')
            ->willThrowException(new RuntimeException('Rollback exploded'))
        ;

        $tester = new CommandTester(new RollbackCommand($migratorService));

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('Rollback exploded', $tester->getDisplay());
        $this->assertStringNotContainsString('Migration rollback successful', $tester->getDisplay());
    }
}
