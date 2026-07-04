<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cache;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Cycle\ClearCycleSchemaCache;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function bin2hex;
use function file_exists;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class ClearCycleSchemaCacheTest extends TestCase
{
    private string $tmpDir;
    private string $schemaPath;
    private CompiledSchemaStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_clear_schema_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        mkdir($this->tmpDir, 0o777, true);

        $this->schemaPath = $this->tmpDir . '/schema.php';
        $this->storage    = new CompiledSchemaStorage();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->schemaPath)) {
            unlink($this->schemaPath);
        }

        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteCacheClearedSuccessfully(): void
    {
        $this->storage->save($this->schemaPath, [
            'foo' => 'bar',
        ]);

        $command       = new ClearCycleSchemaCache($this->storage, $this->schemaPath, true);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '[OK] Cycle ORM schema cache file has been cleared successfully.',
            $commandTester->getDisplay()
        );
        $this->assertFalse(file_exists($this->schemaPath));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteCacheFileNotFound(): void
    {
        $command = new ClearCycleSchemaCache($this->storage, $this->schemaPath, true);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '[NOTE] No compiled schema file was found to clear.',
            $commandTester->getDisplay()
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithDisabledCache(): void
    {
        $command = new ClearCycleSchemaCache($this->storage, $this->schemaPath, false);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '[NOTE] Schema cache is disabled by configuration. Nothing to clear.',
            $commandTester->getDisplay()
        );
    }
}
