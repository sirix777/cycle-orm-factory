<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test;

use PHPUnit\Framework\TestCase;
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
        $this->assertArrayHasKey(CommandName::MigrationRun->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationRollback->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedRun->value, $commands);
        $this->assertArrayHasKey(CommandName::SchemaCompile->value, $commands);
        $this->assertArrayHasKey(CommandName::SchemaSync->value, $commands);
    }
}
