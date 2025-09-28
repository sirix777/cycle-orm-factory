<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Sirix\Cycle\Command\Migrator\CreateSeedCommand;
use Sirix\Cycle\Command\Migrator\MigrateCommand;
use Sirix\Cycle\Command\Migrator\RollbackCommand;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\ConfigProvider;
use Sirix\Cycle\Enum\CommandName;

use function class_exists;
use function putenv;

final class ConfigProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
    }

    public function testMigrationCommandsRegisteredWhenMigrationsPresent(): void
    {
        self::assertTrue(class_exists('Cycle\Migrations\Migrator'), 'Cycle Migrations package must be installed for this test.');

        $provider = new ConfigProvider();
        $config = $provider->__invoke();

        $commands = $config['laminas-cli']['commands'];

        $this->assertArrayHasKey(CommandName::RunMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::RollbackMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::GenerateMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::GenerateSeed->value, $commands);
        $this->assertArrayHasKey(CommandName::RunSeed->value, $commands);

        $this->assertArrayHasKey(CommandName::ClearCache->value, $commands);

        $deps = $provider->getDependencies();
        $factories = $deps['factories'];
        $this->assertArrayHasKey(MigrateCommand::class, $factories);
        $this->assertArrayHasKey(RollbackCommand::class, $factories);
        $this->assertArrayHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayHasKey(SeedCommand::class, $factories);
    }

    public function testMigrationCommandsNotRegisteredWhenDisabledByEnv(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED=1');

        $provider = new ConfigProvider();
        $config = $provider->__invoke();

        $commands = $config['laminas-cli']['commands'];

        $this->assertArrayNotHasKey(CommandName::RunMigration->value, $commands);
        $this->assertArrayNotHasKey(CommandName::RollbackMigration->value, $commands);
        $this->assertArrayNotHasKey(CommandName::GenerateMigration->value, $commands);
        $this->assertArrayNotHasKey(CommandName::GenerateSeed->value, $commands);
        $this->assertArrayNotHasKey(CommandName::RunSeed->value, $commands);

        $this->assertArrayHasKey(CommandName::ClearCache->value, $commands);

        $deps = $provider->getDependencies();
        $factories = $deps['factories'];
        $this->assertArrayNotHasKey(MigrateCommand::class, $factories);
        $this->assertArrayNotHasKey(RollbackCommand::class, $factories);
        $this->assertArrayNotHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayNotHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayNotHasKey(SeedCommand::class, $factories);
    }
}
