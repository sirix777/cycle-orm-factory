<?php

declare(strict_types=1);

namespace Sirix\Cycle\Enum;

enum SchemaProperty: int
{
    case SyncTables = 0;
    case GenerateMigrations = 1;
}
