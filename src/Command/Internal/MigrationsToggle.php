<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Internal;

use function class_exists;
use function getenv;
use function in_array;
use function strtolower;
use function trim;

final class MigrationsToggle
{
    private const MIGRATOR_CLASS = 'Cycle\Migrations\Migrator';
    private const GENERATE_MIGRATION_CLASS = 'Cycle\Schema\Generator\Migrations\GenerateMigrations';

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
