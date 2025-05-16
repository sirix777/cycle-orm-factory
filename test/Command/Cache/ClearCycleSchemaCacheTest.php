<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cache;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Sirix\Cycle\Command\Cycle\ClearCycleSchemaCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ClearCycleSchemaCacheTest extends TestCase
{
    private CacheItemPoolInterface|MockObject $cache;
    private ClearCycleSchemaCache $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->command = new ClearCycleSchemaCache($this->cache, 'cache_key');
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testExecuteCacheClearedSuccessfully(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with('cache_key')
            ->willReturn(true)
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Cycle ORM schema cache has been cleared successfully.', $output);
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testExecuteCacheEntryNotFound(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with('cache_key')
            ->willReturn(false)
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[NOTE] No cache entry was found to clear.', $output);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testExecuteCacheClearingFails(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with('cache_key')
            ->willThrowException(new RuntimeException('Unexpected error'))
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Failed to clear Cycle ORM schema cache: Unexpected error', $output);
        $this->AssertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
