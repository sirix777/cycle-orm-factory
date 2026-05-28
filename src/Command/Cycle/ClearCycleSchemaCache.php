<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class ClearCycleSchemaCache extends Command
{
    public function __construct(
        private readonly CompiledSchemaStorage $compiledSchemaStorage,
        private readonly string $compiledSchemaPath,
        private readonly bool $isCacheEnabled,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::CacheClear->value)
            ->setDescription('Clears the Cycle ORM schema cache file')
            ->setHelp('This command clears the compiled schema file used by Cycle ORM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        if (! $this->isCacheEnabled) {
            $symfonyStyle->note('Schema cache is disabled by configuration. Nothing to clear.');

            return Command::SUCCESS;
        }

        try {
            $deleted = $this->compiledSchemaStorage->clear($this->compiledSchemaPath);

            if ($deleted) {
                $symfonyStyle->success('Cycle ORM schema cache file has been cleared successfully.');
            } else {
                $symfonyStyle->note('No compiled schema file was found to clear.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $symfonyStyle->error('Failed to clear Cycle ORM schema cache file: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
