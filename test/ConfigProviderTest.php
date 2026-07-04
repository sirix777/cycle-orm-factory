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
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;

use function class_exists;

final class ConfigProviderTest extends TestCase
{
    public function testBaseAndMigrationLayersAreRegisteredWhenMigrationsPresent(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle Migrations package is not installed in this environment.');
        }

        $provider = new ConfigProvider();
        $config   = $provider->__invoke();
        $commands = $config['laminas-cli']['commands'];

        $this->assertArrayHasKey(CommandName::SchemaCompile->value, $commands);
        $this->assertArrayHasKey(CommandName::SchemaSync->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationRun->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationRollback->value, $commands);
        $this->assertArrayHasKey(CommandName::MigrationCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedCreate->value, $commands);
        $this->assertArrayHasKey(CommandName::SeedRun->value, $commands);
        $this->assertArrayHasKey(CommandName::CacheClear->value, $commands);

        $deps       = $provider->getDependencies();
        $factories  = $deps['factories'];
        $aliases    = $deps['aliases'];
        $invokables = $deps['invokables'];

        $this->assertArrayHasKey(SchemaCompileCommand::class, $factories);
        $this->assertArrayHasKey(SchemaSyncCommand::class, $factories);
        $this->assertArrayHasKey('migrator', $factories);
        $this->assertArrayHasKey(MigratorService::class, $factories);
        $this->assertArrayHasKey(MigrateCommand::class, $factories);
        $this->assertArrayHasKey(RollbackCommand::class, $factories);
        $this->assertArrayHasKey(CreateMigrationCommand::class, $factories);
        $this->assertArrayHasKey(CreateSeedCommand::class, $factories);
        $this->assertArrayHasKey(SeedCommand::class, $factories);

        $this->assertArrayHasKey(SchemaCompilerInterface::class, $aliases);
        $this->assertSame(SchemaCompilerService::class, $aliases[SchemaCompilerInterface::class]);
        $this->assertArrayHasKey(MigratorInterface::class, $aliases);
        $this->assertSame('migrator', $aliases[MigratorInterface::class]);
        $this->assertArrayHasKey(CompiledSchemaStorage::class, $invokables);
    }
}
