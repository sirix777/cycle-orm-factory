<?php

declare(strict_types=1);

namespace Sirix\Cycle\Enum;

enum SchemaCompileMode
{
    case Runtime;
    case SyncTables;
    case GenerateMigrations;
}
