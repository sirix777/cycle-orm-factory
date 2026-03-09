<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Cycle\Migrations\Migrator;
use Cycle\ORM\Entity\Behavior\EventListener;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;
use Symfony\Component\Console\Command\Command;

use function class_exists;

/**
 * @internal
 */
final class PackageChecker
{
    public static function isConsoleAvailable(): bool
    {
        return class_exists(Command::class);
    }

    public static function isEntityBehaviorAvailable(): bool
    {
        return class_exists(EventListener::class);
    }

    public static function isMigratorAvailable(): bool
    {
        return class_exists(Migrator::class);
    }

    public static function isGenerateMigrationsAvailable(): bool
    {
        return class_exists(GenerateMigrations::class);
    }
}
