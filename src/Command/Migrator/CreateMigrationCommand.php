<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use DateTime;
use Exception;
use Sirix\Cycle\Command\Helper\FileNameValidator;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\MigratorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function glob;
use function md5;
use function microtime;
use function preg_match;
use function random_bytes;
use function sprintf;
use function ucfirst;

final class CreateMigrationCommand extends Command
{
    private const UNIQUE_ID_LENGTH = 18;
    private const DEFAULT_DATABASE = 'main-db';

    private const MIGRATION_TEMPLATE = <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace %s;

        use Cycle\Migrations\Migration;

        class %s extends Migration
        {
            protected const DATABASE = '%s';

            public function up(): void
            {
            }

            public function down(): void
            {
            }
        }
        PHP;

    public function __construct(
        private readonly string $migrationDirectory,
        private readonly MigratorInterface $migrator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::GenerateMigration->value)
            ->setDescription('Create an empty migration file')
            ->addArgument('migrationName', InputArgument::REQUIRED, 'The name of the migration in PascalCase format')
            ->addOption(
                'database',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Database alias to use in generated migration (constant DATABASE)',
                self::DEFAULT_DATABASE
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $migrationName = (string) $input->getArgument('migrationName');

        if (! FileNameValidator::isPascalCase($migrationName)) {
            $io->error('Invalid migration name. Use PascalCase format.');

            return Command::FAILURE;
        }

        $filename = $this->generateMigrationName($migrationName);

        $filePath = $this->migrationDirectory . DIRECTORY_SEPARATOR . $filename;

        $className = sprintf('Orm%s', ucfirst($this->getUniqueId()));

        /** @var null|string $database */
        $database = $input->getOption('database');
        $database = null === $database || '' === $database ? self::DEFAULT_DATABASE : $database;

        $fileContent = $this->getMigrationFileContent($className, $database);

        $filesystem = new Filesystem();

        try {
            $filesystem->dumpFile($filePath, $fileContent);
            $io->success("Migration created: {$filePath}");
        } catch (Exception $e) {
            $io->error("Failed to create migration: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getMigrationFileContent(string $className, string $database): string
    {
        return sprintf(
            self::MIGRATION_TEMPLATE,
            $this->migrator->getConfig()->getNamespace(),
            $className,
            $database,
        );
    }

    private function generateMigrationName(string $migrationName): string
    {
        $timestamp = (new DateTime())->format('Ymd.His');
        $counter = $this->findNextCounter($migrationName);

        return sprintf('%s_0_%d_%s.php', $timestamp, $counter, $migrationName);
    }

    private function findNextCounter(string $migrationName): int
    {
        $pattern = $this->migrationDirectory . DIRECTORY_SEPARATOR . '*_*_*_' . $migrationName . '.php';
        $existingFiles = glob($pattern);

        if ([] === $existingFiles || false === $existingFiles) {
            return 0;
        }

        $maxCounter = -1;
        foreach ($existingFiles as $file) {
            if (preg_match('/\d{8}\.\d{6}_(\d+)_(\d+)_' . $migrationName . '\.php$/', $file, $matches)) {
                $counter = (int) $matches[1];
                if ($counter > $maxCounter) {
                    $maxCounter = $counter;
                }
            }
        }

        return $maxCounter + 1;
    }

    private function getUniqueId(): string
    {
        try {
            return bin2hex(random_bytes(self::UNIQUE_ID_LENGTH));
        } catch (Exception) {
            return bin2hex(md5(microtime()));
        }
    }
}
