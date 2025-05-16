<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Throwable;

class MigratorService
{
    public function __construct(private readonly MigratorInterface $migrator) {}

    public function migrate(callable $output): void
    {
        if (! $this->migrator->isConfigured()) {
            $this->migrator->configure();
        }

        while (($migration = $this->migrator->run()) !== null) {
            $output('Migrating ' . $migration->getState()->getName());
        }
    }

    /**
     * @throws Throwable
     */
    public function rollback(): void
    {
        if (! $this->migrator->isConfigured()) {
            $this->migrator->configure();
        }

        $this->migrator->rollback();
    }

    /**
     * Seeds the database using the provided SeedInterface implementation.
     *
     * @param SeedInterface $seed The seed implementation to run
     *
     * @throws Throwable
     */
    public function seed(SeedInterface $seed): void
    {
        $seed->run();
    }
}
