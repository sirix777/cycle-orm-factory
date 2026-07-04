<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Internal;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Sirix\Cycle\Command\Migrator\CreateSeedCommand;
use Sirix\Cycle\Command\Migrator\MigrateCommand;
use Sirix\Cycle\Command\Migrator\RollbackCommand;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Internal\MigrationsLayer;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;

use function class_exists;

final class MigrationsLayerTest extends TestCase
{
    public function testDependenciesContainMigratorServicesAndCommandFactories(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle Migrations package is not installed in this environment.');
        }

        $dependencies = (new MigrationsLayer())->getDependencies();

        $this->assertSame('migrator', $dependencies['aliases'][MigratorInterface::class]);
        $this->assertArrayHasKey('migrator', $dependencies['factories']);
        $this->assertArrayHasKey(MigratorService::class, $dependencies['factories']);
        $this->assertArrayHasKey(MigrateCommand::class, $dependencies['factories']);
        $this->assertArrayHasKey(RollbackCommand::class, $dependencies['factories']);
        $this->assertArrayHasKey(CreateMigrationCommand::class, $dependencies['factories']);
        $this->assertArrayHasKey(CreateSeedCommand::class, $dependencies['factories']);
        $this->assertArrayHasKey(SeedCommand::class, $dependencies['factories']);
    }

    public function testCommandsContainMigrationAndSeedCommands(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle Migrations package is not installed in this environment.');
        }

        $commands = (new MigrationsLayer())->getCommands();

        $this->assertSame(MigrateCommand::class, $commands[CommandName::MigrationRun->value]);
        $this->assertSame(RollbackCommand::class, $commands[CommandName::MigrationRollback->value]);
        $this->assertSame(CreateMigrationCommand::class, $commands[CommandName::MigrationCreate->value]);
        $this->assertSame(CreateSeedCommand::class, $commands[CommandName::SeedCreate->value]);
        $this->assertSame(SeedCommand::class, $commands[CommandName::SeedRun->value]);
    }
}
