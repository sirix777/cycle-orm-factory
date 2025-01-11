<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\RepositoryInterface;

interface MigratorInterface
{
    public function isConfigured(): bool;

    public function configure(): void;

    public function run(): ?MigrationInterface;

    public function rollback(): void;

    public function getRepository(): RepositoryInterface;

    public function getConfig(): MigrationConfig;
}
