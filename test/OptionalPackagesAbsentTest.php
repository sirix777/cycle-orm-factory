<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\ConfigProvider;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;

use function class_exists;

final class OptionalPackagesAbsentTest extends TestCase
{
    public function testConfigProviderDoesNotRegisterOptionalLayersWhenPackagesAreMissing(): void
    {
        if (
            class_exists('Symfony\Component\Console\Command\Command')
            || class_exists('Cycle\ORM\Entity\Behavior\EventListener')
            || class_exists('Cycle\Migrations\Migrator')
            || class_exists('Cycle\Schema\Generator\Migrations\GenerateMigrations')
        ) {
            $this->markTestSkipped('Optional packages are installed in this environment.');
        }

        $config       = (new ConfigProvider())->__invoke();
        $dependencies = $config['dependencies'];
        $commands     = $config['laminas-cli']['commands'];

        $this->assertSame([], $commands);

        $this->assertSame('orm', $dependencies['aliases']['Cycle\ORM\ORMInterface']);
        $this->assertSame('dbal', $dependencies['aliases']['Cycle\Database\DatabaseInterface']);
        $this->assertSame(SchemaCompilerService::class, $dependencies['aliases'][SchemaCompilerInterface::class]);
        $this->assertArrayNotHasKey(MigratorInterface::class, $dependencies['aliases']);

        $this->assertArrayHasKey('orm', $dependencies['factories']);
        $this->assertArrayHasKey('dbal', $dependencies['factories']);
        $this->assertArrayHasKey(SchemaCompilerService::class, $dependencies['factories']);
        $this->assertArrayNotHasKey('migrator', $dependencies['factories']);
        $this->assertArrayNotHasKey(MigratorService::class, $dependencies['factories']);

        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Cycle\ClearCycleSchemaCache', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Cycle\SchemaCompileCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Cycle\SchemaSyncCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Cycle\SchemaMigrationsGenerateCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Migrator\MigrateCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Migrator\RollbackCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Migrator\CreateMigrationCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Migrator\CreateSeedCommand', $dependencies['factories']);
        $this->assertArrayNotHasKey('Sirix\Cycle\Command\Migrator\SeedCommand', $dependencies['factories']);
    }
}
