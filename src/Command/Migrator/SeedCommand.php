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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function class_exists;
use function file_exists;
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
            ->setDescription('Run a specific seed file')
            ->addArgument(
                'seed',
                InputArgument::REQUIRED,
                'The name of the seed file to run (without .php extension)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $seedName = (string) $input->getArgument('seed');

        if (! $this->validateSeedName($seedName, $io)) {
            return Command::FAILURE;
        }

        $seedFile = $this->getSeedFilePath($seedName);
        if (! $this->validateSeedFileExists($seedFile, $seedName, $io)) {
            return Command::FAILURE;
        }

        require_once $seedFile;

        $seedClassName = $this->getSeedClassName($seedName);
        if (! $this->validateSeedClassExists($seedClassName, $io)) {
            return Command::FAILURE;
        }

        $seed = new $seedClassName();

        if (! $this->injectDatabase($seed, $io)) {
            return Command::FAILURE;
        }

        if (! $seed instanceof SeedInterface) {
            $io->error(sprintf('Seed class "%s" must implement SeedInterface.', $seedClassName));

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

    private function validateSeedName(string $seedName, SymfonyStyle $io): bool
    {
        if ('' === $seedName || '0' === $seedName) {
            $io->error('Seed name is required.');

            return false;
        }

        if (! FileNameValidator::isPascalCase($seedName)) {
            $io->error('Invalid seed name. Use PascalCase format.');

            return false;
        }

        return true;
    }

    private function getSeedFilePath(string $seedName): string
    {
        return sprintf('%s%s%s.php', $this->seedDirectory, DIRECTORY_SEPARATOR, $seedName);
    }

    private function validateSeedFileExists(string $seedFile, string $seedName, SymfonyStyle $io): bool
    {
        if (! file_exists($seedFile)) {
            $io->error(sprintf('Seed file "%s" not found in the seed directory.', $seedName));

            return false;
        }

        return true;
    }

    private function getSeedClassName(string $seedName): string
    {
        return sprintf('%s\%s', self::SEED_NAMESPACE, $seedName);
    }

    private function validateSeedClassExists(string $seedClassName, SymfonyStyle $io): bool
    {
        if (! class_exists($seedClassName)) {
            $io->error(sprintf(
                'Seed class "%s" not found in the file. Make sure the class name matches the file name.',
                $seedClassName
            ));

            return false;
        }

        return true;
    }

    private function injectDatabase(object $seed, SymfonyStyle $io): bool
    {
        try {
            $reflectionClass = new ReflectionClass($seed);

            $databaseName = $reflectionClass->getConstant(self::SEED_DATABASE_CONSTANT) ?? self::DEFAULT_DATABASE;
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
