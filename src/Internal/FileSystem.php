<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use RuntimeException;

use function dirname;
use function file_exists;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function scandir;
use function sprintf;
use function unlink;

final class FileSystem
{
    public function writeFile(string $path, string $contents): void
    {
        $this->ensureDirectory(dirname($path));

        if (false === file_put_contents($path, $contents)) {
            throw new RuntimeException(sprintf('Unable to write file: %s', $path));
        }
    }

    public function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (file_exists($directory)) {
            throw new RuntimeException(sprintf('Path exists and is not a directory: %s', $directory));
        }

        if (! mkdir($directory, 0o777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $directory));
        }
    }

    /**
     * @param array<int, string>|string $paths
     */
    public function remove(array|string $paths): void
    {
        $items = is_array($paths) ? $paths : [$paths];

        foreach ($items as $item) {
            if (! file_exists($item)) {
                continue;
            }

            if (is_file($item)) {
                if (! unlink($item)) {
                    throw new RuntimeException(sprintf('Unable to remove file: %s', $item));
                }

                continue;
            }

            $this->removeDirectory($item);
        }
    }

    private function removeDirectory(string $directory): void
    {
        $entries = scandir($directory);
        if (false === $entries) {
            throw new RuntimeException(sprintf('Unable to read directory: %s', $directory));
        }

        foreach ($entries as $entry) {
            if ('.' === $entry) {
                continue;
            }

            if ('..' === $entry) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } elseif (! unlink($path)) {
                throw new RuntimeException(sprintf('Unable to remove file: %s', $path));
            }
        }

        if (! rmdir($directory)) {
            throw new RuntimeException(sprintf('Unable to remove directory: %s', $directory));
        }
    }
}
