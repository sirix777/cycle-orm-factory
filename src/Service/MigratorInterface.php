<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Migrations\MigrationInterface;

interface MigratorInterface
{
    public function isConfigured(): bool;

    public function configure(): void;

    public function run(): ?MigrationInterface;

    public function rollback(): void;
}
