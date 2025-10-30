<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use Cycle\Migrations\Config\MigrationConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Sirix\Cycle\Service\MigratorInterface;
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
use function usleep;

final class CreateMigrationCommandTest extends TestCase
{
    private string $migrationDirectory;

    /** @var MigratorInterface&MockObject */
    private MigratorInterface $migrator;

    protected function setUp(): void
    {
        $this->migrationDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('migrations_', true);
        mkdir($this->migrationDirectory, 0o777, true);

        $config = new MigrationConfig([
            'namespace' => 'Migration',
        ]);

        $this->migrator = $this->createMock(MigratorInterface::class);
        $this->migrator->method('getConfig')->willReturn($config);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->migrationDirectory);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithValidMigrationName(): void
    {
        $command = new CreateMigrationCommand($this->migrationDirectory, $this->migrator);

        $input = new ArrayInput(['migrationName' => 'ValidMigrationName']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);
        $outputContent = $output->fetch();

        $this->assertSame(Command::SUCCESS, $resultCode);
        $this->assertStringContainsString('Migration created:', $outputContent);

        $migrations = glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $this->assertCount(1, $migrations);

        /** @var non-empty-list<string> $migrations */
        $migrationFileName = basename($migrations[0]);
        $this->assertMatchesRegularExpression('/^\d{8}\.\d{6}_0_\d+_ValidMigrationName\.php$/', $migrationFileName);

        $fileContent = file_get_contents($migrations[0]);
        $this->assertIsString($fileContent);
        $this->assertStringContainsString('namespace Migration;', $fileContent);
        $this->assertStringContainsString('class Orm', $fileContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithInvalidMigrationName(): void
    {
        $command = new CreateMigrationCommand($this->migrationDirectory, $this->migrator);

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
        $command = new CreateMigrationCommand('/dev/null', $this->migrator);

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
        $command = new CreateMigrationCommand($this->migrationDirectory, $this->migrator);

        $input = new ArrayInput(['migrationName' => 'DuplicateName']);
        $output = new BufferedOutput();
        $command->run($input, $output);

        usleep(200_000);

        $input = new ArrayInput(['migrationName' => 'DuplicateName']);
        $output = new BufferedOutput();
        $command->run($input, $output);

        $migrations = glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*DuplicateName.php') ?: [];
        $this->assertCount(2, $migrations);

        $counters = [];
        foreach ($migrations as $migration) {
            $filename = basename($migration);
            if (preg_match('/^\d{8}\.\d{6}_0_(\d+)_DuplicateName\.php$/', $filename, $matches)) {
                $counters[] = (int) $matches[1];
            }
        }

        $this->assertContains(0, $counters);
        $this->assertContains(1, $counters);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteCreatesMigrationInDifferentDirectories(): void
    {
        $dir1 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('migrations_a_', true);
        $dir2 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('migrations_b_', true);
        mkdir($dir1, 0o777, true);
        mkdir($dir2, 0o777, true);

        $command1 = new CreateMigrationCommand($dir1, $this->migrator);
        $command2 = new CreateMigrationCommand($dir2, $this->migrator);

        $input = new ArrayInput(['migrationName' => 'CrossDirTest']);
        $out1 = new BufferedOutput();
        $out2 = new BufferedOutput();

        $command1->run($input, $out1);
        $command2->run($input, $out2);

        $files1 = glob($dir1 . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $files2 = glob($dir2 . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $this->assertCount(1, $files1);
        $this->assertCount(1, $files2);

        // @var non-empty-list<string> $files1
        // @var non-empty-list<string> $files2
        $this->assertMatchesRegularExpression('/^\d{8}\.\d{6}_0_\d+_CrossDirTest\.php$/', basename($files1[0]));
        $this->assertMatchesRegularExpression('/^\d{8}\.\d{6}_0_\d+_CrossDirTest\.php$/', basename($files2[0]));

        (new Filesystem())->remove([$dir1, $dir2]);
    }
}
