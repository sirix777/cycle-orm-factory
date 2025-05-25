<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Command\Migrator\MigrateCommand;
use Sirix\Cycle\Service\MigratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateCommandTest extends TestCase
{
    private MigratorService|MockObject $migratorService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->migratorService = $this->createMock(MigratorService::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecute(): void
    {
        $this->migratorService
            ->expects($this->once())
            ->method('migrate')
            ->willReturnCallback(function(callable $output) {
                $output('Migrating test-migration');
            })
        ;

        $result = $this->runCommand([]);

        $this->assertCommandResult(
            $result,
            Command::SUCCESS,
            'Migrating test-migration'
        );
        $this->assertCommandResult(
            $result,
            Command::SUCCESS,
            'Migration successful'
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteHandlesMigratorServiceException(): void
    {
        $exception = new RuntimeException('Test exception');

        $this->migratorService
            ->expects($this->once())
            ->method('migrate')
            ->willThrowException($exception)
        ;

        $result = $this->runCommand([]);

        $this->assertCommandResult(
            $result,
            Command::FAILURE,
            'An error occurred during migration: Test exception'
        );
    }

    /**
     * Runs the command with the given input and returns the result.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     *
     * @throws ExceptionInterface
     */
    private function runCommand(array $input): array
    {
        $command = $this->getMigrateCommand();
        $output = new BufferedOutput();

        $resultCode = $command->run(new ArrayInput($input), $output);
        $outputContent = $output->fetch();

        return [
            'code' => $resultCode,
            'output' => $outputContent,
        ];
    }

    /**
     * Asserts that the command result matches the expected values.
     *
     * @param array<string, mixed> $result
     */
    private function assertCommandResult(array $result, int $expectedCode, string $expectedOutputContains): void
    {
        $this->assertSame($expectedCode, $result['code']);
        $this->assertStringContainsString($expectedOutputContains, $result['output']);
    }

    private function getMigrateCommand(): MigrateCommand
    {
        return new MigrateCommand($this->migratorService);
    }
}
