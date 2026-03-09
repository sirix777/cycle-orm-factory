<?php

declare(strict_types=1);

namespace Sirix\Cycle\Enum;

enum CommandName: string
{
    case MigrationCreate = 'cycle:migration:create';
    case MigrationRollback = 'cycle:migration:rollback';
    case MigrationRun = 'cycle:migration:run';
    case CacheClear = 'cycle:cache:clear';
    case SeedCreate = 'cycle:seed:create';
    case SeedRun = 'cycle:seed:run';
    case SchemaCompile = 'cycle:schema:compile';
    case SchemaSync = 'cycle:schema:sync';
    case SchemaMigrationGenerate = 'cycle:schema:migration:generate';
}
