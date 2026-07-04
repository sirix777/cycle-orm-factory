<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Command\Cycle\SchemaMigrationsGenerateCommand;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function bin2hex;
use function file_exists;
use function is_file;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class SchemaMigrationsGenerateCommandTest extends TestCase
{
    private MockObject|SchemaCompilerInterface $schemaCompiler;
    private CompiledSchemaStorage $storage;
    private DatabaseManager $dbal;
    private string $tmpDir;
    private string $schemaPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_schema_migrations_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        mkdir($this->tmpDir, 0o777, true);
        $this->schemaPath = $this->tmpDir . '/schema.php';

        $this->schemaCompiler = $this->createMock(SchemaCompilerInterface::class);
        $this->storage = new CompiledSchemaStorage();
        $this->dbal = new DatabaseManager(new DatabaseConfig([]));
    }

    protected function tearDown(): void
    {
        if (is_file($this->schemaPath)) {
            unlink($this->schemaPath);
        }

        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    public function testExecuteWithEnabledCacheRefreshesCompiledSchema(): void
    {
        $schema = ['foo' => 'bar'];
        $this->schemaCompiler
            ->expects($this->once())
            ->method('generateMigrations')
            ->with($this->dbal, ['src/Entity'], [], [])
            ->willReturn($schema)
        ;

        $command = new SchemaMigrationsGenerateCommand(
            $this->schemaCompiler,
            $this->storage,
            $this->dbal,
            ['src/Entity'],
            [],
            [],
            $this->schemaPath,
            true,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertTrue(file_exists($this->schemaPath));
        $this->assertSame($schema, $this->storage->load($this->schemaPath));
        $this->assertStringContainsString(
            'Schema migrations generated and compiled cache refreshed.',
            $tester->getDisplay(),
        );
    }

    public function testExecuteWithDisabledCacheSkipsCompiledSchemaSave(): void
    {
        $this->schemaCompiler
            ->expects($this->once())
            ->method('generateMigrations')
            ->with($this->dbal, [], [], [])
            ->willReturn(['foo' => 'bar'])
        ;

        $command = new SchemaMigrationsGenerateCommand(
            $this->schemaCompiler,
            $this->storage,
            $this->dbal,
            [],
            [],
            [],
            $this->schemaPath,
            false,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFalse(file_exists($this->schemaPath));
        $this->assertStringContainsString(
            'Schema migrations generated. Compiled cache update skipped',
            $tester->getDisplay(),
        );
    }

    public function testExecuteReturnsFailureOnCompilerError(): void
    {
        $this->schemaCompiler
            ->expects($this->once())
            ->method('generateMigrations')
            ->willThrowException(new RuntimeException('migration generation failed'))
        ;

        $command = new SchemaMigrationsGenerateCommand(
            $this->schemaCompiler,
            $this->storage,
            $this->dbal,
            [],
            [],
            [],
            $this->schemaPath,
            true,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFalse(file_exists($this->schemaPath));
        $this->assertStringContainsString(
            'Failed to generate schema migrations: migration generation failed',
            $tester->getDisplay(),
        );
    }
}
