<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Sirix\Cycle\Command\Migrator\SeedCommand;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\SeedInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

use function file_exists;
use function file_put_contents;
use function glob;
use function mkdir;
use function rename;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

class SeedCommandTest extends TestCase
{
    private const SEED_TEMPLATE = <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace Seed;

        use Sirix\Cycle\Service\SeedInterface;
        use Cycle\Database\DatabaseInterface;

        class %s implements %s
        {
            protected const DATABASE = 'main-db';
            protected DatabaseInterface $database;

            public function run(): void
            {
                // Test implementation
                %s
            }
        }
        PHP;

    /** @var MigratorService&MockObject */
    private MigratorService $migratorService;

    /** @var DatabaseProviderInterface&MockObject */
    private DatabaseProviderInterface $dbal;
    private string $seedDirectory;

    protected function setUp(): void
    {
        $this->migratorService = $this->createMock(MigratorService::class);
        $this->dbal = $this->createMock(DatabaseProviderInterface::class);
        $this->seedDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'seeds_' . uniqid();

        $database = $this->createMock(DatabaseInterface::class);
        $this->dbal->method('database')->willReturn($database);

        if (! file_exists($this->seedDirectory)) {
            mkdir($this->seedDirectory, 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if (file_exists($this->seedDirectory)) {
            $filesystem->remove($this->seedDirectory);
        }
    }

    public function testExecuteWithNoSeedName(): void
    {
        $this->createSeedFile('TestSeed');
        $result = $this->runCommand([]);
        $this->assertCommandResult($result, Command::SUCCESS, 'All 1 seeds executed successfully.');
    }

    public function testExecuteWithThreeSeeds(): void
    {
        $this->createSeedFile('FirstSeed');
        $this->createSeedFile('SecondSeed');
        $this->createSeedFile('ThirdSeed');
        $result = $this->runCommand([], 3);
        $this->assertCommandResult($result, Command::SUCCESS, 'All 3 seeds executed successfully.');
    }

    public function testExecuteWithNoSeedFiles(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->seedDirectory);
        mkdir($this->seedDirectory, 0o777, true);

        $result = $this->runCommand([], 0);

        $this->assertCommandResult(
            $result,
            Command::SUCCESS,
            sprintf('No seed files found in directory "%s".', $this->seedDirectory)
        );
    }

    public function testExecuteWithInvalidSeedName(): void
    {
        $result = $this->runCommand(['seed' => 'invalid_seed_name'], null);
        $this->assertCommandResult($result, Command::FAILURE, 'Invalid seed name. Use PascalCase format.');
    }

    public function testExecuteWithNonExistentSeedFile(): void
    {
        $result = $this->runCommand(['seed' => 'NonExistentSeed'], null);

        $this->assertStringContainsString('Seed file "NonExistentSeed" not found in directory', $result['output']);
        $this->assertStringContainsString($this->seedDirectory, $result['output']);
    }

    public function testExecuteWithInvalidSeedClass(): void
    {
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

        $result = $this->runCommand(['seed' => 'InvalidSeed'], null);
        $this->assertSame(Command::FAILURE, $result['code']);
    }

    public function testExecuteWithMismatchedClassName(): void
    {
        $this->createSeedFile('MismatchedSeed', SeedInterface::class, '', 'DifferentClassName');
        $seed = (array) glob($this->seedDirectory . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $seed);

        $result = $this->runCommand(['seed' => 'MismatchedSeed'], null);
        $this->assertCommandResult($result, Command::FAILURE, 'Seed class "Seed\MismatchedSeed" not found in the file');
    }

    public function testExecuteWithSuccessfulSeed(): void
    {
        $this->createSeedFile('SuperSeed');
        $result = $this->runCommand(['seed' => 'SuperSeed']);
        $this->assertCommandResult($result, Command::SUCCESS, 'Seed "SuperSeed" executed successfully.');
    }

    public function testExecuteWithSeedOption(): void
    {
        $this->createSeedFile('OptionSeed');
        $result = $this->runCommand(['--seed' => 'OptionSeed']);
        $this->assertCommandResult($result, Command::SUCCESS, 'Seed "OptionSeed" executed successfully.');
    }

    public function testExecuteWithShortSeedOption(): void
    {
        $this->createSeedFile('ShortOptionSeed');
        $result = $this->runCommand(['-s' => 'ShortOptionSeed']);
        $this->assertCommandResult($result, Command::SUCCESS, 'Seed "ShortOptionSeed" executed successfully.');
    }

    public function testExecuteHandlesMigratorServiceException(): void
    {
        $this->createSeedFile('ExceptionSeed');
        $exception = new RuntimeException('Test exception');

        $result = $this->runCommand(['seed' => 'ExceptionSeed', '__exception' => $exception]);
        $this->assertCommandResult($result, Command::FAILURE, 'Failed to run seed: Test exception');
    }

    public function testExecuteWithCustomDirectoryOption(): void
    {
        $customDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'custom_seeds_' . uniqid();
        mkdir($customDir, 0o777, true);

        $this->createSeedFile('CustomDirSeed');
        rename(
            $this->seedDirectory . DIRECTORY_SEPARATOR . 'CustomDirSeed.php',
            $customDir . DIRECTORY_SEPARATOR . 'CustomDirSeed.php'
        );

        $result = $this->runCommand(['--directory' => $customDir, '--seed' => 'CustomDirSeed']);
        $this->assertCommandResult($result, Command::SUCCESS, 'Seed "CustomDirSeed" executed successfully.');

        (new Filesystem())->remove($customDir);
    }

    public function testExecuteWithShortDirectoryOption(): void
    {
        $customDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shortdir_' . uniqid();
        mkdir($customDir, 0o777, true);

        $this->createSeedFile('ShortDirSeed');
        rename(
            $this->seedDirectory . DIRECTORY_SEPARATOR . 'ShortDirSeed.php',
            $customDir . DIRECTORY_SEPARATOR . 'ShortDirSeed.php'
        );

        $result = $this->runCommand(['-d' => $customDir, '--seed' => 'ShortDirSeed']);
        $this->assertCommandResult($result, Command::SUCCESS, 'Seed "ShortDirSeed" executed successfully.');

        (new Filesystem())->remove($customDir);
    }

    /**
     * @param class-string $interface
     */
    private function createSeedFile(
        string $seedName,
        string $interface = SeedInterface::class,
        string $additionalCode = '',
        ?string $className = null
    ): void {
        $className ??= $seedName;
        $interfaceName = (new ReflectionClass($interface))->getShortName();

        $seedContent = sprintf(self::SEED_TEMPLATE, $className, $interfaceName, $additionalCode);
        $seedFile = $this->seedDirectory . DIRECTORY_SEPARATOR . $seedName . '.php';
        file_put_contents($seedFile, $seedContent);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{code:int, output:string}
     */
    private function runCommand(array $input, ?int $expectedMigratorCalls = 1): array
    {
        $command = $this->getSeedCommand();
        $output = new BufferedOutput();

        if (null !== $expectedMigratorCalls) {
            $expectation = $this->migratorService->expects($expectedMigratorCalls > 0
                ? $this->exactly($expectedMigratorCalls)
                : $this->never());
            if (isset($input['__exception'])) {
                $expectation->method('seed')->willThrowException($input['__exception']);
                unset($input['__exception']);
            } else {
                $expectation->method('seed');
            }
        }

        $resultCode = $command->run(new ArrayInput($input), $output);
        $outputContent = $output->fetch();

        return [
            'code' => $resultCode,
            'output' => $outputContent,
        ];
    }

    /**
     * @param array{code:int, output:string} $result
     */
    private function assertCommandResult(array $result, int $expectedCode, string $expectedOutputContains): void
    {
        $this->assertSame($expectedCode, $result['code']);
        $this->assertStringContainsString($expectedOutputContains, $result['output']);
    }

    private function getSeedCommand(): SeedCommand
    {
        return new SeedCommand($this->migratorService, $this->seedDirectory, $this->dbal);
    }
}
