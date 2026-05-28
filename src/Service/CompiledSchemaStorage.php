<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\ORM\Exception\ConfigException;
use Sirix\Cycle\Internal\FileSystem;

use function bin2hex;
use function dirname;
use function file_exists;
use function function_exists;
use function is_array;
use function is_file;
use function opcache_invalidate;
use function random_bytes;
use function rename;
use function sprintf;
use function unlink;
use function var_export;

final readonly class CompiledSchemaStorage
{
    private FileSystem $fileSystem;

    public function __construct()
    {
        $this->fileSystem = new FileSystem();
    }

    public function has(string $path): bool
    {
        return is_file($path);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    public function load(string $path): array
    {
        if (! $this->has($path)) {
            throw new ConfigException(sprintf('Compiled schema file "%s" does not exist.', $path));
        }

        $schema = require $path;
        if (! is_array($schema)) {
            throw new ConfigException(sprintf('Compiled schema file "%s" must return an array.', $path));
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $schema
     */
    public function save(string $path, array $schema): void
    {
        $directory = dirname($path);
        $this->fileSystem->ensureDirectory($directory);

        $tmpPath = sprintf('%s.%s.tmp', $path, bin2hex(random_bytes(8)));

        $this->fileSystem->writeFile($tmpPath, $this->buildPhpFileContent($schema));
        if (file_exists($path)) {
            unlink($path);
        }

        if (! rename($tmpPath, $path)) {
            throw new ConfigException(sprintf('Unable to move temporary compiled schema file to "%s".', $path));
        }

        $this->invalidateOpcache($path);
    }

    public function clear(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        $this->fileSystem->remove($path);
        $this->invalidateOpcache($path);

        return true;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function buildPhpFileContent(array $schema): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($schema, true) . ";\n";
    }

    private function invalidateOpcache(string $path): void
    {
        if (! function_exists('opcache_invalidate')) {
            return;
        }

        @opcache_invalidate($path, true);
    }
}
