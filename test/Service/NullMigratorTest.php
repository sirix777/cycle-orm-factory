<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sirix\Cycle\Service\NullMigrator;

final class NullMigratorTest extends TestCase
{
    private const ERROR_MESSAGE = 'Cycle migrations are unavailable. Install "cycle/migrations" or remove env "CYCLE_MIGRATIONS_DISABLED" to enable.';
    private NullMigrator $migrator;

    public function setUp(): void
    {
        parent::setUp();
        $this->migrator = new NullMigrator();
    }

    public function testIsConfiguredIsFalse(): void
    {
        $this->assertFalse($this->migrator->isConfigured());
    }

    public function testConfigureThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ERROR_MESSAGE);
        $this->migrator->configure();
    }

    public function testRunReturnsNull(): void
    {
        $this->assertNull($this->migrator->run());
    }

    public function testRollbackThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ERROR_MESSAGE);
        $this->migrator->rollback();
    }

    public function testGetRepositoryThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ERROR_MESSAGE);
        $this->migrator->getRepository();
    }

    public function testGetConfigThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ERROR_MESSAGE);
        $this->migrator->getConfig();
    }
}
