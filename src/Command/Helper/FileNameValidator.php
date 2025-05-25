<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Helper;

use function preg_match;

final class FileNameValidator
{
    /**
     * Validates that the given name follows PascalCase format
     * (starts with an uppercase letter followed by alphanumeric characters).
     */
    public static function isPascalCase(string $name): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9]+$/', $name);
    }
}
