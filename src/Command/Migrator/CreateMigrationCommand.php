<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use const DIRECTORY_SEPARATOR;

use DateTime;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function md5;
use function microtime;
use function preg_match;
use function random_bytes;
use function sprintf;
use function ucfirst;

class CreateMigrationCommand extends Command
{
    public function __construct(private readonly string $migrationDirectory, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('migrator:create-migration')
            ->setDescription('Create an empty migration file')
            ->addArgument('migrationName', InputArgument::REQUIRED, 'The name of the migration in PascalCase format')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrationName = $input->getArgument('migrationName');

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $migrationName)) {
            $output->writeln('<error>Invalid migration name. Use PascalCase format.</error>');

            return Command::FAILURE;
        }

        $filename = $this->generateMigrationName($migrationName);

        $filePath = $this->migrationDirectory . DIRECTORY_SEPARATOR . $filename;

        $className = sprintf('Orm%s', ucfirst($this->getUniqueId()));
        $fileContent = $this->getMigrationFileContent($className);

        $filesystem = new Filesystem();

        try {
            $filesystem->dumpFile($filePath, $fileContent);
            $output->writeln("<info>Migration created: {$filePath}</info>");
        } catch (Exception $e) {
            $output->writeln("<error>Failed to create migration: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getMigrationFileContent(string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Migration;

        use Cycle\\Migrations\\Migration;

        class {$className} extends Migration
        {
            protected const string DATABASE = 'main-db';

            public function up(): void
            {
            }

            public function down(): void
            {
            }
        }
        PHP;
    }

    private function generateMigrationName(string $migrationName): string
    {
        $timestamp = (new DateTime())->format('Ymd.His');

        return sprintf('%s_0_0_%s.php', $timestamp, $migrationName);
    }

    private function getUniqueId(): string
    {
        try {
            return bin2hex(random_bytes(18));
        } catch (Exception) {
            return bin2hex(md5(microtime()));
        }
    }
}
