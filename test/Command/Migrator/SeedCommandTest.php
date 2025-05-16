<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\Service\MigratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

use function file_exists;
use function file_put_contents;
use function glob;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class SeedCommandTest extends TestCase
{
    private MigratorService|MockObject $migratorService;
    private DatabaseProviderInterface|MockObject $dbal;
    private string $seedDirectory;

    protected function setUp(): void
    {
        $this->migratorService = $this->createMock(MigratorService::class);
        $this->dbal = $this->createMock(DatabaseProviderInterface::class);
        $this->seedDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'seeds_' . uniqid();

        $database = $this->createMock(DatabaseInterface::class);

        $this->dbal->method('database')->willReturn($database);

        // Create the seed directory
        if (! file_exists($this->seedDirectory)) {
            mkdir($this->seedDirectory, 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up the seed directory
        $filesystem = new Filesystem();
        if (file_exists($this->seedDirectory)) {
            $filesystem->remove($this->seedDirectory);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithMissingSeedName(): void
    {
        $command = $this->getSeedCommand();

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "seed").');
        $command->run($input, $output);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithInvalidSeedName(): void
    {
        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'invalid_seed_name']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Invalid seed name. Use PascalCase format.', $outputContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithNonExistentSeedFile(): void
    {
        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'NonExistentSeed']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString(
            'Seed file "NonExistentSeed" not found in the seed directory',
            $outputContent
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithInvalidSeedClass(): void
    {
        // Create a seed file with a class that doesn't implement SeedInterface
        $seedContent = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Seed;

            class InvalidSeed
            {
                public function run(): void
                {
                    // This class doesn't implement SeedInterface
                }
            }
            PHP;

        $seedFile = $this->seedDirectory . DIRECTORY_SEPARATOR . 'InvalidSeed.php';
        file_put_contents($seedFile, $seedContent);

        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'InvalidSeed']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $this->assertSame(Command::FAILURE, $resultCode);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithMismatchedClassName(): void
    {
        $database = '$database';
        $seedContent = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Seed;

            use Sirix\\Cycle\\Service\\SeedInterface;
            use Cycle\\Database\\DatabaseInterface;

            class DifferentClassName implements SeedInterface
            {
                protected const DATABASE = 'main-db';
                protected DatabaseInterface {$database};

                public function run(): void
                {
                    // Class name doesn't match file name
                }
            }
            PHP;

        $seedFile = $this->seedDirectory . DIRECTORY_SEPARATOR . 'MismatchedSeed.php';
        file_put_contents($seedFile, $seedContent);

        $seed = (array) glob($this->seedDirectory . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $seed);

        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'MismatchedSeed']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Seed class "Seed\MismatchedSeed" not found in the file', $outputContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithSuccessfulSeed(): void
    {
        $database = '$database';
        $seedContent = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Seed;

            use Sirix\\Cycle\\Service\\SeedInterface;
            use Cycle\\Database\\DatabaseInterface;

            class TestSeed implements SeedInterface
            {
                protected const DATABASE = 'main-db';
                protected DatabaseInterface {$database};

                public function run(): void
                {
                    // Test implementation
                }
            }
            PHP;

        $seedFile = $this->seedDirectory . DIRECTORY_SEPARATOR . 'TestSeed.php';
        file_put_contents($seedFile, $seedContent);

        $this->migratorService
            ->expects($this->once())
            ->method('seed')
        ;

        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'TestSeed']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::SUCCESS, $resultCode);
        $this->assertStringContainsString('Seed "TestSeed" executed successfully.', $outputContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteHandlesMigratorServiceException(): void
    {
        $database = '$database';
        $seedContent = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Seed;

            use Sirix\\Cycle\\Service\\SeedInterface;
            use Cycle\\Database\\DatabaseInterface;

            class ExceptionSeed implements SeedInterface
            {
                protected const DATABASE = 'main-db';
                protected DatabaseInterface {$database};

                public function run(): void
                {
                    // Test implementation
                }
            }
            PHP;

        $seedFile = $this->seedDirectory . DIRECTORY_SEPARATOR . 'ExceptionSeed.php';
        file_put_contents($seedFile, $seedContent);

        $exception = new RuntimeException('Test exception');

        $this->migratorService
            ->expects($this->once())
            ->method('seed')
            ->willThrowException($exception)
        ;

        $command = $this->getSeedCommand();

        $input = new ArrayInput(['seed' => 'ExceptionSeed']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Failed to run seed: Test exception', $outputContent);
    }

    private function getSeedCommand(): SeedCommand
    {
        /** @var DatabaseProviderInterface $dbal */
        $dbal = $this->dbal;

        return new SeedCommand(
            $this->migratorService,
            $this->seedDirectory,
            $dbal,
        );
    }
}
