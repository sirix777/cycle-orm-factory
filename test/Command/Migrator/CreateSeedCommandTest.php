<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Migrator\CreateSeedCommand;
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

class CreateSeedCommandTest extends TestCase
{
    private string $seedDirectory;

    protected function setUp(): void
    {
        $this->seedDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('seeds_', true);
        mkdir($this->seedDirectory, 0o777, true);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->seedDirectory);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithValidSeedName(): void
    {
        $command = $this->getCreateSeedCommand();

        $input = new ArrayInput(['seed' => 'ValidSeedName']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertSame(Command::SUCCESS, $resultCode);
        $this->assertStringContainsString('Seed created:', $outputContent);

        $seeds = (array) glob($this->seedDirectory . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $seeds);

        $seedFileName = basename((string) $seeds[0]);
        $this->assertSame('ValidSeedName.php', $seedFileName);

        $fileContent = file_get_contents((string) $seeds[0]);
        $this->assertStringContainsString('namespace Seed;', (string) $fileContent);
        $this->assertStringContainsString('class ValidSeedName implements SeedInterface', (string) $fileContent);
        $this->assertStringContainsString('public function run(): void', (string) $fileContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithMissingSeedName(): void
    {
        $command = $this->getCreateSeedCommand();

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Seed name is required.', $outputContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithInvalidSeedName(): void
    {
        $command = $this->getCreateSeedCommand();

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
    public function testExecuteHandlesFilesystemException(): void
    {
        $command = $this->getCreateSeedCommand('/dev/null');

        $input = new ArrayInput(['seed' => 'ValidSeedName']);
        $output = new BufferedOutput();

        $resultCode = $command->run($input, $output);

        $outputContent = $output->fetch();

        $this->assertSame(Command::FAILURE, $resultCode);
        $this->assertStringContainsString('Failed to create seed:', $outputContent);
    }

    private function getCreateSeedCommand(?string $dir = null): CreateSeedCommand
    {
        return new CreateSeedCommand(
            $dir ?? $this->seedDirectory,
        );
    }
}
