<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Migrations\Migrator;
use Throwable;

use const PHP_EOL;

class MigratorService
{
    public function __construct(private readonly Migrator $migrator)
    {
    }

    public function migrate(): void
    {
        if (! $this->migrator->isConfigured()) {
            $this->migrator->configure();
        }

        while (($migration = $this->migrator->run()) !== null) {
            echo 'Migrating ' . $migration->getState()->getName() . PHP_EOL;
        }

        echo 'Migrate successful' . PHP_EOL;
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

        echo 'Rollback successful' . PHP_EOL;
    }
}
