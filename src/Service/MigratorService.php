<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use const PHP_EOL;

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
    //    public function migrate(): void
    //    {
    //        if (! $this->migrator->isConfigured()) {
    //            $this->migrator->configure();
    //        }
    //
    //        while (($migration = $this->migrator->run()) !== null) {
    //            echo 'Migrating ' . $migration->getState()->getName() . PHP_EOL;
    //        }
    //
    //        echo 'Migrate successful' . PHP_EOL;
    //    }

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
}
