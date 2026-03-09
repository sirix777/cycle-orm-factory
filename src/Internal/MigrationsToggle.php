<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use function getenv;
use function in_array;
use function strtolower;
use function trim;

final class MigrationsToggle
{
    public static function isDisabledByEnv(): bool
    {
        $flag = getenv('CYCLE_MIGRATIONS_DISABLED');

        if (false === $flag) {
            return false;
        }
        $flag = strtolower(trim((string) $flag));

        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    public static function areMigrationsEnabled(): bool
    {
        return PackageChecker::isMigratorAvailable() && ! self::isDisabledByEnv();
    }
}
