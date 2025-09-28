<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

use function basename;
use function file_get_contents;
use function glob;
use function mkdir;
use function preg_match;
use function sys_get_temp_dir;
use function uniqid;

class CreateMigrationCommandTest extends TestCase
{
    private string $migrationDirectory;

    protected function setUp(): void
    {
        $this->migrationDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('migrations_', true);
        mkdir($this->migrationDirectory, 0o777, true);
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
        $this->assertSame(Command::SUCCESS, $resultCode);
        $this->assertStringContainsString('Migration created:', $outputContent);

        $migrations = (array) glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $migrations);

        $migrationFileName = basename((string) $migrations[0]);
        $this->assertMatchesRegularExpression('/^\d{8}\.\d{6}_0_\d+_ValidMigrationName\.php$/', $migrationFileName);

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

        $this->assertSame(Command::FAILURE, $resultCode);
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

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteIncrementsCounterForDuplicateNames(): void
    {
        $command = new CreateMigrationCommand($this->migrationDirectory);

        $input = new ArrayInput(['migrationName' => 'DuplicateName']);
        $output = new BufferedOutput();
        $command->run($input, $output);

        $input = new ArrayInput(['migrationName' => 'DuplicateName']);
        $output = new BufferedOutput();
        $command->run($input, $output);

        $migrations = (array) glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*DuplicateName.php');
        $this->assertCount(2, $migrations);

        $counters = [];
        $timestamp = null;

        foreach ($migrations as $migration) {
            $filename = basename((string) $migration);
            if (preg_match('/^(\d{8}\.\d{6})_0_(\d+)_DuplicateName\.php$/', $filename, $matches)) {
                if (null === $timestamp) {
                    $timestamp = $matches[1];
                }
                $counters[] = (int) $matches[2];
            }
        }

        $this->assertCount(2, $counters);
        $this->assertContains(0, $counters);
        $this->assertContains(1, $counters);
    }
}
