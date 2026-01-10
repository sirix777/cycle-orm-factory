<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Cycle\Migrations\Migrator;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;

use function class_exists;
use function getenv;
use function in_array;
use function strtolower;
use function trim;

final class MigrationsToggle
{
    private const MIGRATOR_CLASS = Migrator::class;
    private const GENERATE_MIGRATION_CLASS = GenerateMigrations::class;

    public static function isDisabledByEnv(): bool
    {
        $flag = getenv('CYCLE_MIGRATIONS_DISABLED');

        if (false === $flag) {
            return false;
        }
        $flag = strtolower(trim((string) $flag));

        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    public static function isMigratorAvailable(): bool
    {
        return class_exists(self::MIGRATOR_CLASS);
    }

    public static function isGenerateMigrationsAvailable(): bool
    {
        return class_exists(self::GENERATE_MIGRATION_CLASS);
    }

    public static function areMigrationsEnabled(): bool
    {
        return self::isMigratorAvailable() && ! self::isDisabledByEnv();
    }
}
