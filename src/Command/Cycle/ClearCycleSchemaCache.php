<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Psr\Cache\CacheItemPoolInterface;
use Sirix\Cycle\Enum\CommandName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class ClearCycleSchemaCache extends Command
{
    public function __construct(
        private readonly ?CacheItemPoolInterface $cache,
        private readonly string $cacheKey,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::ClearCache->value)
            ->setDescription('Clears the Cycle ORM schema cache')
            ->setHelp('This command clears the cached schema for Cycle ORM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! $this->cache instanceof CacheItemPoolInterface) {
            $io->note('Schema cache is disabled by configuration. Nothing to clear.');

            return Command::SUCCESS;
        }

        try {
            $deleted = $this->cache->deleteItem($this->cacheKey);

            if ($deleted) {
                $io->success('Cycle ORM schema cache has been cleared successfully.');
            } else {
                $io->note('No cache entry was found to clear.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error('Failed to clear Cycle ORM schema cache: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
