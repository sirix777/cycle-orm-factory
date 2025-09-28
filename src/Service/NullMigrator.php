<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\RepositoryInterface;
use RuntimeException;

/**
 * Fallback implementation used when Cycle Migrations package is not installed.
 */
final class NullMigrator implements MigratorInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function configure(): void
    {
        $this->throwException();
    }

    public function run(): ?MigrationInterface
    {
        return null;
    }

    public function rollback(): void
    {
        $this->throwException();
    }

    public function getRepository(): RepositoryInterface
    {
        $this->throwException();
    }

    public function getConfig(): MigrationConfig
    {
        $this->throwException();
    }

    private function throwException(): never
    {
        throw new RuntimeException(
            'Cycle migrations are unavailable. Install "cycle/migrations" or remove env "CYCLE_MIGRATIONS_DISABLED" to enable.'
        );
    }
}
