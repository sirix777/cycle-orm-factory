<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\Migrator;
use Throwable;

class MigratorWrapper implements MigratorInterface
{
    public function __construct(private readonly Migrator $migrator) {}

    public function isConfigured(): bool
    {
        return $this->migrator->isConfigured();
    }

    public function configure(): void
    {
        $this->migrator->configure();
    }

    public function run(): ?MigrationInterface
    {
        return $this->migrator->run();
    }

    /**
     * @throws Throwable
     */
    public function rollback(): void
    {
        $this->migrator->rollback();
    }
}
