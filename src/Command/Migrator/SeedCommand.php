<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Cycle\Database\DatabaseProviderInterface;
use ReflectionClass;
use Sirix\Cycle\Command\Helper\FileNameValidator;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\SeedInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function class_exists;
use function file_exists;
use function glob;
use function is_dir;
use function is_string;
use function pathinfo;
use function rtrim;
use function sprintf;

final class SeedCommand extends Command
{
    private const DEFAULT_DATABASE = 'main-db';
    private const SEED_NAMESPACE = 'Seed';
    private const SEED_DATABASE_CONSTANT = 'DATABASE';
    private const SEED_DATABASE_PROPERTY = 'database';

    public function __construct(
        private readonly MigratorService $migratorService,
        private readonly string $seedDirectory,
        private readonly DatabaseProviderInterface $dbal,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::RunSeed->value)
            ->setDescription('Run seed files')
            ->addArgument(
                'seed',
                InputArgument::OPTIONAL,
                'The name of the seed file to run (without .php extension)'
            )
            ->addOption(
                'seed',
                's',
                InputOption::VALUE_OPTIONAL,
                'The name of the seed file to run (without .php extension)'
            )
            ->addOption(
                'directory',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The directory from which to run seeds (optional, overrides default seed directory)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seedDirectory = $this->resolveSeedDirectory($input);
        $seedName = $this->getSeedNameFromInput($input);

        if ('' !== $seedName && '0' !== $seedName) {
            return $this->executeSingleSeed($seedName, $seedDirectory, $io);
        }

        return $this->executeAllSeeds($seedDirectory, $io);
    }

    private function resolveSeedDirectory(InputInterface $input): string
    {
        $customDir = (string) $input->getOption('directory');

        if ('' !== $customDir && is_dir($customDir)) {
            return rtrim($customDir, DIRECTORY_SEPARATOR);
        }

        return $this->seedDirectory;
    }

    private function getSeedNameFromInput(InputInterface $input): string
    {
        $seedName = (string) $input->getArgument('seed');
        if ('' === $seedName || '0' === $seedName) {
            return (string) $input->getOption('seed');
        }

        return $seedName;
    }

    private function executeSingleSeed(string $seedName, string $seedDirectory, SymfonyStyle $io): int
    {
        $seed = $this->loadAndValidateSeed($seedName, $seedDirectory, $io);
        if (! $seed instanceof SeedInterface) {
            return Command::FAILURE;
        }

        try {
            $this->migratorService->seed($seed);
            $io->success(sprintf('Seed "%s" executed successfully.', $seedName));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error(sprintf('Failed to run seed: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function loadAndValidateSeed(string $seedName, string $seedDirectory, SymfonyStyle $io): ?SeedInterface
    {
        if (! $this->validateSeedName($seedName, $io)) {
            return null;
        }

        $seedFile = $this->getSeedFilePath($seedName, $seedDirectory);
        if (! file_exists($seedFile)) {
            $io->error(sprintf('Seed file "%s" not found in directory "%s".', $seedName, $seedDirectory));

            return null;
        }

        require_once $seedFile;

        $seedClassName = $this->getSeedClassName($seedName);
        if (! class_exists($seedClassName)) {
            $io->error(sprintf(
                'Seed class "%s" not found in the file. Make sure the class name matches the file name.',
                $seedClassName
            ));

            return null;
        }

        $seed = new $seedClassName();

        if (! $this->injectDatabase($seed, $io)) {
            return null;
        }

        if (! $seed instanceof SeedInterface) {
            $io->error(sprintf('Seed class "%s" must implement SeedInterface.', $seedClassName));

            return null;
        }

        return $seed;
    }

    private function executeAllSeeds(string $seedDirectory, SymfonyStyle $io): int
    {
        $seedFiles = glob($seedDirectory . DIRECTORY_SEPARATOR . '*.php');

        if ([] === $seedFiles || false === $seedFiles) {
            $io->warning(sprintf('No seed files found in directory "%s".', $seedDirectory));

            return Command::SUCCESS;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($seedFiles as $seedFile) {
            $seedName = pathinfo($seedFile, PATHINFO_FILENAME);
            $io->section(sprintf('Running seed: %s', $seedName));

            $result = $this->executeSingleSeed($seedName, $seedDirectory, $io);

            Command::SUCCESS === $result ? ++$successCount : ++$failureCount;
        }

        if ($failureCount > 0) {
            $io->error(sprintf(
                'Seed execution completed with errors. %d succeeded, %d failed.',
                $successCount,
                $failureCount
            ));

            return Command::FAILURE;
        }

        $io->success(sprintf('All %d seeds executed successfully.', $successCount));

        return Command::SUCCESS;
    }

    private function validateSeedName(string $seedName, SymfonyStyle $io): bool
    {
        if (! FileNameValidator::isPascalCase($seedName)) {
            $io->error('Invalid seed name. Use PascalCase format.');

            return false;
        }

        return true;
    }

    private function getSeedFilePath(string $seedName, string $seedDirectory): string
    {
        return sprintf('%s%s%s.php', $seedDirectory, DIRECTORY_SEPARATOR, $seedName);
    }

    private function getSeedClassName(string $seedName): string
    {
        return sprintf('%s\%s', self::SEED_NAMESPACE, $seedName);
    }

    private function injectDatabase(object $seed, SymfonyStyle $io): bool
    {
        try {
            $reflectionClass = new ReflectionClass($seed);

            $databaseName = self::DEFAULT_DATABASE;
            if ($reflectionClass->hasConstant(self::SEED_DATABASE_CONSTANT)) {
                $constValue = $reflectionClass->getConstant(self::SEED_DATABASE_CONSTANT);
                if (is_string($constValue) && '' !== $constValue) {
                    $databaseName = $constValue;
                }
            }

            $database = $this->dbal->database($databaseName);

            if ($reflectionClass->hasProperty(self::SEED_DATABASE_PROPERTY)) {
                $databaseProperty = $reflectionClass->getProperty(self::SEED_DATABASE_PROPERTY);
                $databaseProperty->setValue($seed, $database);
            }

            return true;
        } catch (Throwable $e) {
            $io->warning(sprintf('Could not inject database into seed class: %s', $e->getMessage()));

            return false;
        }
    }
}
