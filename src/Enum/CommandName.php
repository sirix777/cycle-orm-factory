<?php

namespace Sirix\Cycle\Enum;

enum CommandName: string
{
    case GenerateMigrations = 'cycle:migrator:generate';
    case RollbackMigrations = 'cycle:migrator:rollback';
    case RunMigrations = 'cycle:migrator:run';
    case ClearCache = 'cycle:cache:clear';
}
