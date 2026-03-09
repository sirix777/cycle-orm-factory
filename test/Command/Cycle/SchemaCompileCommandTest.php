<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommand;
use Sirix\Cycle\Enum\SchemaCompileMode;
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

final class SchemaCompileCommandTest extends TestCase
{
    private MockObject|SchemaCompilerInterface $schemaCompiler;
    private CompiledSchemaStorage $storage;
    private DatabaseManager $dbal;
    private string $tmpDir;
    private string $schemaPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_schema_compile_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
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

    public function testExecuteCompilesAndSavesSchema(): void
    {
        $schema = ['foo' => 'bar'];
        $entities = ['src/Entity'];
        $manualMapping = ['m' => ['x' => 'y']];
        $additionalGenerators = ['my.generator.service'];

        $this->schemaCompiler
            ->expects($this->once())
            ->method('compile')
            ->with($this->dbal, $entities, $manualMapping, $additionalGenerators, SchemaCompileMode::Runtime)
            ->willReturn($schema)
        ;

        $command = new SchemaCompileCommand(
            $this->schemaCompiler,
            $this->storage,
            $this->dbal,
            $entities,
            $manualMapping,
            $additionalGenerators,
            $this->schemaPath,
            true,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertTrue(file_exists($this->schemaPath));
        $this->assertSame($schema, $this->storage->load($this->schemaPath));
        $this->assertStringContainsString('Cycle ORM schema compiled and saved to:', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureOnCompilerError(): void
    {
        $this->schemaCompiler
            ->expects($this->once())
            ->method('compile')
            ->willThrowException(new RuntimeException('compile failed'))
        ;

        $command = new SchemaCompileCommand(
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
        $this->assertStringContainsString('Failed to compile schema: compile failed', $tester->getDisplay());
    }
}
