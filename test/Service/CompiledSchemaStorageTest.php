<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use Cycle\ORM\Exception\ConfigException;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Service\CompiledSchemaStorage;

use function bin2hex;
use function file_exists;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class CompiledSchemaStorageTest extends TestCase
{
    private string $tmpDir;
    private string $schemaPath;
    private CompiledSchemaStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_compiled_schema_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
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

    public function testSaveAndLoadSchema(): void
    {
        $schema = [
            'foo' => [
                'bar' => 'baz',
            ],
        ];

        $this->storage->save($this->schemaPath, $schema);

        $this->assertTrue($this->storage->has($this->schemaPath));
        $this->assertSame($schema, $this->storage->load($this->schemaPath));
    }

    public function testLoadThrowsWhenFileMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('does not exist');

        $this->storage->load($this->schemaPath);
    }

    public function testClearRemovesFile(): void
    {
        $this->storage->save($this->schemaPath, [
            'a' => 1,
        ]);

        $this->assertTrue($this->storage->clear($this->schemaPath));
        $this->assertFalse(file_exists($this->schemaPath));
        $this->assertFalse($this->storage->clear($this->schemaPath));
    }
}
