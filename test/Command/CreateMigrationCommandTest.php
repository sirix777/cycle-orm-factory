<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command;

use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

use function basename;
use function file_get_contents;
use function glob;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

class CreateMigrationCommandTest extends TestCase
{
    private string $migrationDirectory;

    protected function setUp(): void
    {
        $this->migrationDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('migrations_', true);
        mkdir($this->migrationDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->migrationDirectory);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithValidMigrationName(): void
    {
        $command = new CreateMigrationCommand($this->migrationDirectory);

        $input = new ArrayInput(['migrationName' => 'ValidMigrationName']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertSame(0, $resultCode);
        $this->assertStringContainsString('Migration created:', $outputContent);

        $migrations = (array) glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $migrations);

        $migrationFileName = basename((string) $migrations[0]);
        $this->assertMatchesRegularExpression('/^\d{8}\.\d{6}_0_0_ValidMigrationName\.php$/', $migrationFileName);

        $fileContent = file_get_contents((string) $migrations[0]);
        $this->assertStringContainsString('namespace Migration;', (string) $fileContent);
        $this->assertStringContainsString('class Orm', (string) $fileContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithInvalidMigrationName(): void
    {
        $command = new CreateMigrationCommand($this->migrationDirectory);

        $input = new ArrayInput(['migrationName' => 'invalid_migration_name']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(1, $resultCode);
        $this->assertStringContainsString('Invalid migration name. Use PascalCase format.', $outputContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteHandlesFilesystemException(): void
    {
        $command = new CreateMigrationCommand('/dev/null');

        $input = new ArrayInput(['migrationName' => 'ValidMigrationName']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Failed to create migration:', $outputContent);
    }
}
