<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Command\Cycle\ClearCycleSchemaCacheFactory;
use Sirix\Cycle\Internal\FileSystem;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Test\Support\ArrayContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function bin2hex;
use function file_exists;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

final class ClearCycleSchemaCacheFactoryTest extends TestCase
{
    private string $tmpDir;
    private string $schemaPath;
    private CompiledSchemaStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir     = sprintf('%s/cycle_clear_cache_factory_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        $this->schemaPath = $this->tmpDir . '/schema.php';
        $this->storage    = new CompiledSchemaStorage();
        $this->storage->save($this->schemaPath, [
            'foo' => 'bar',
        ]);
    }

    protected function tearDown(): void
    {
        (new FileSystem())->remove($this->tmpDir);

        parent::tearDown();
    }

    public function testClearsConfiguredPathWithCacheEnabledByDefault(): void
    {
        $tester = $this->runCommand([
            'schema' => [
                'compiled' => [
                    'path' => $this->schemaPath,
                ],
            ],
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertFalse(file_exists($this->schemaPath));
    }

    public function testKeepsFileWhenCacheIsDisabled(): void
    {
        $tester = $this->runCommand([
            'schema' => [
                'cache'    => [
                    'enabled' => false,
                ],
                'compiled' => [
                    'path' => $this->schemaPath,
                ],
            ],
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue(file_exists($this->schemaPath));
        $this->assertStringContainsString('Schema cache is disabled', $tester->getDisplay());
    }

    /**
     * @param array<string, mixed> $cycleConfig
     */
    private function runCommand(array $cycleConfig): CommandTester
    {
        $command = (new ClearCycleSchemaCacheFactory())(new ArrayContainer([
            'config'                     => [
                'cycle' => $cycleConfig,
            ],
            CompiledSchemaStorage::class => $this->storage,
        ]));

        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester;
    }
}
