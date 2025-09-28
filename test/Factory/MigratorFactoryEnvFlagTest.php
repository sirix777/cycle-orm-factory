<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Cycle\Factory\MigratorFactory;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\NullMigrator;

use function class_exists;
use function putenv;

final class MigratorFactoryEnvFlagTest extends TestCase
{
    private ContainerInterface|MockObject $container;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->config = [
            'cycle' => [
                'migrator' => [
                    'directory' => 'db/migrations',
                    'table' => 'migrations',
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        parent::tearDown();
    }

    public function testReturnsNullMigratorWhenDisabledByEnv(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED=1');

        $this->container->method('has')->with('config')->willReturn(true);
        $this->container->method('get')->willReturnMap([
            ['config', $this->config],
            ['dbal', new DatabaseManager(new DatabaseConfig([]))],
        ]);

        $factory = new MigratorFactory();
        $instance = $factory($this->container);

        $this->assertInstanceOf(NullMigrator::class, $instance);
    }

    public function testFlagZeroIsTreatedAsEnabled(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle migrations package is not installed in this environment.');
        }

        putenv('CYCLE_MIGRATIONS_DISABLED=0');

        $this->container->method('has')->with('config')->willReturn(true);
        $this->container->method('get')->willReturnMap([
            ['config', $this->config],
            ['dbal', new DatabaseManager(new DatabaseConfig([]))],
        ]);

        $factory = new MigratorFactory();
        $instance = $factory($this->container);

        $this->assertInstanceOf(MigratorInterface::class, $instance);
        $this->assertNotInstanceOf(NullMigrator::class, $instance);
    }
}
