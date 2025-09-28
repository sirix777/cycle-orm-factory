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

final class ConfigProviderFlagZeroTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        parent::tearDown();
    }

    public function testMigrationCommandsRegisteredWhenFlagZero(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle Migrations package must be installed for this test.');
        }

        putenv('CYCLE_MIGRATIONS_DISABLED=0');

        $provider = new ConfigProvider();
        $config = $provider->__invoke();

        $commands = $config['laminas-cli']['commands'];
        $this->assertArrayHasKey(CommandName::RunMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::RollbackMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::GenerateMigration->value, $commands);
        $this->assertArrayHasKey(CommandName::GenerateSeed->value, $commands);
        $this->assertArrayHasKey(CommandName::RunSeed->value, $commands);

        $deps = $provider->getDependencies();
        $factories = $deps['factories'];
        $this->assertArrayHasKey(MigrateCommand::class, $factories);
        $this->assertArrayHasKey(RollbackCommand::class, $factories);
        $this->assertArrayHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayHasKey(SeedCommand::class, $factories);
    }
}
