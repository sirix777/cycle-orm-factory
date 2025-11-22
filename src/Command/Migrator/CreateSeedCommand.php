<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use Exception;
use Sirix\Cycle\Command\Helper\FileNameValidator;
use Sirix\Cycle\Enum\CommandName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function sprintf;

final class CreateSeedCommand extends Command
{
    private const DEFAULT_DATABASE = 'main-db';

    private const SEED_TEMPLATE = <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace Seed;

        use Sirix\Cycle\Service\SeedInterface;
        use Cycle\Database\DatabaseInterface;

        final class %s implements SeedInterface
        {
            private const DATABASE = '%s';
            private DatabaseInterface $database;

            public function run(): void
            {
                // Implement seed logic here
            }
        }
        PHP;

    public function __construct(private readonly string $seedDirectory, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::GenerateSeed->value)
            ->setDescription('Create a seed file')
            ->addArgument('seed', InputOption::VALUE_REQUIRED, 'The name of the seed in PascalCase format')
            ->addOption(
                'database',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Database alias to use in generated seed (constant DATABASE)',
                self::DEFAULT_DATABASE
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seedName = (string) $input->getArgument('seed');

        if ('' === $seedName || '0' === $seedName) {
            $io->error('Seed name is required.');

            return Command::FAILURE;
        }

        if (! FileNameValidator::isPascalCase($seedName)) {
            $io->error('Invalid seed name. Use PascalCase format.');

            return Command::FAILURE;
        }

        $filename = $this->generateSeedName($seedName);
        $filePath = $this->seedDirectory . DIRECTORY_SEPARATOR . $filename;

        /** @var null|string $database */
        $database = $input->getOption('database');
        $database = null === $database || '' === $database ? self::DEFAULT_DATABASE : $database;

        $fileContent = $this->getSeedFileContent($seedName, $database);

        $filesystem = new Filesystem();

        try {
            $filesystem->dumpFile($filePath, $fileContent);
            $io->success("Seed created: {$filePath}");
        } catch (Exception $e) {
            $io->error("Failed to create seed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getSeedFileContent(string $className, string $database): string
    {
        return sprintf(self::SEED_TEMPLATE, $className, $database);
    }

    private function generateSeedName(string $seedName): string
    {
        return sprintf('%s.php', $seedName);
    }
}
