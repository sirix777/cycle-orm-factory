<?php

declare(strict_types=1);

namespace Sirix\Cycle\Enum;

enum CommandName: string
{
    case GenerateMigration = 'cycle:migrator:generate';
    case RollbackMigration = 'cycle:migrator:rollback';
    case RunMigration = 'cycle:migrator:run';
    case ClearCache = 'cycle:cache:clear';
}
