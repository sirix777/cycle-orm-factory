<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use DateTime;
use Exception;
use Sirix\Cycle\Command\Helper\FileNameValidator;
use Sirix\Cycle\Enum\CommandName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function md5;
use function microtime;
use function random_bytes;
use function sprintf;
use function ucfirst;

final class CreateMigrationCommand extends Command
{
    private const UNIQUE_ID_LENGTH = 18;

    private const MIGRATION_TEMPLATE = <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace Migration;

        use Cycle\Migrations\Migration;

        class %s extends Migration
        {
            protected const DATABASE = 'main-db';

            public function up(): void
            {
            }

            public function down(): void
            {
            }
        }
        PHP;

    public function __construct(private readonly string $migrationDirectory, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::GenerateMigration->value)
            ->setDescription('Create an empty migration file')
            ->addArgument('migrationName', InputArgument::REQUIRED, 'The name of the migration in PascalCase format')
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
        $fileContent = $this->getMigrationFileContent($className);

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

    private function getMigrationFileContent(string $className): string
    {
        return sprintf(self::MIGRATION_TEMPLATE, $className);
    }

    private function generateMigrationName(string $migrationName): string
    {
        $timestamp = (new DateTime())->format('Ymd.His');

        return sprintf('%s_0_0_%s.php', $timestamp, $migrationName);
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
