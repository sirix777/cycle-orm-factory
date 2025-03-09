<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\MigratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class MigrateCommand extends Command
{
    public function __construct(private readonly MigratorService $migratorService, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::RunMigrations->value)
            ->setDescription('Run migrations')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Starting Migration Process');

        try {
            $this->migratorService->migrate(function(string $message) use ($io): void {
                $io->writeln($message);
            });
        } catch (Throwable $exception) {
            $io->error("An error occurred during migration: {$exception->getMessage()}");

            return Command::FAILURE;
        }

        $io->success('Migration successful');

        return Command::SUCCESS;
    }
}
