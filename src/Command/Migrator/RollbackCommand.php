<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\MigratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class RollbackCommand extends Command
{
    public function __construct(private readonly MigratorService $migratorService, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::RollbackMigrations->value)
            ->setDescription('Rollback last migration')
        ;
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migratorService->rollback();

        return Command::SUCCESS;
    }
}
