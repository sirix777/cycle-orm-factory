<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommand;
use Sirix\Cycle\Command\Cycle\SchemaSyncCommand;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Sirix\Cycle\Command\Migrator\CreateSeedCommand;
use Sirix\Cycle\Command\Migrator\MigrateCommand;
use Sirix\Cycle\Command\Migrator\RollbackCommand;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\ConfigProvider;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;

use function class_exists;
use function putenv;

final class ConfigProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        parent::tearDown();
    }

    public function testCommandsRegisteredWhenMigrationsPresent(): void
    {
        self::assertTrue(class_exists('Cycle\Migrations\Migrator'), 'Cycle Migrations package must be installed for this test.');

        $provider = new ConfigProvider();
        $config = $provider->__invoke();
        $commands = $config['laminas-cli']['commands'];

        $this->assertArrayHasKey(CommandName::SchemaCompile->value, $commands);
        $this->assertArrayHasKey(CommandName::SchemaSync->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationRun->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationRollback->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedRun->value, $commands);
        $this->assertArrayHasKey(CommandName::CacheClear->value, $commands);

        $deps = $provider->getDependencies();
        $factories = $deps['factories'];
        $aliases = $deps['aliases'];
        $invokables = $deps['invokables'];

        $this->assertArrayHasKey(SchemaCompileCommand::class, $factories);
        $this->assertArrayHasKey(SchemaSyncCommand::class, $factories);
        $this->assertArrayHasKey(MigrateCommand::class, $factories);
        $this->assertArrayHasKey(RollbackCommand::class, $factories);
        $this->assertArrayHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayHasKey(SeedCommand::class, $factories);

        $this->assertArrayHasKey(SchemaCompilerInterface::class, $aliases);
        $this->assertSame(SchemaCompilerService::class, $aliases[SchemaCompilerInterface::class]);
        $this->assertArrayHasKey(CompiledSchemaStorage::class, $invokables);
    }

    public function testMigrationCommandsNotRegisteredWhenDisabledByEnv(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED=1');

        $provider = new ConfigProvider();
        $config = $provider->__invoke();
        $commands = $config['laminas-cli']['commands'];

        $this->assertArrayHasKey(CommandName::SchemaCompile->value, $commands);
        $this->assertArrayHasKey(CommandName::SchemaSync->value, $commands);
        $this->assertArrayNotHasKey(CommandName::MigrationRun->value, $commands);
        $this->assertArrayNotHasKey(CommandName::MigrationRollback->value, $commands);
        $this->assertArrayNotHasKey(CommandName::MigrationCreate->value, $commands);
        $this->assertArrayNotHasKey(CommandName::SeedCreate->value, $commands);
        $this->assertArrayNotHasKey(CommandName::SeedRun->value, $commands);
        $this->assertArrayNotHasKey(CommandName::SchemaMigrationGenerate->value, $commands);

        $deps = $provider->getDependencies();
        $factories = $deps['factories'];

        $this->assertArrayNotHasKey(MigrateCommand::class, $factories);
        $this->assertArrayNotHasKey(RollbackCommand::class, $factories);
        $this->assertArrayNotHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayNotHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayNotHasKey(SeedCommand::class, $factories);
    }
}
