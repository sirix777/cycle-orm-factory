<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\Exception\InvalidContainerServiceException;
use Sirix\ContainerResolver\Exception\MissingContainerServiceException;
use Sirix\Cycle\Factory\DatabaseFactory;
use stdClass;

use function array_key_exists;

final class DatabaseFactoryTest extends TestCase
{
    public function testReturnsDefaultDatabaseOfSharedManager(): void
    {
        $manager   = $this->createManager();
        $container = $this->createContainer([
            'dbal' => $manager,
        ]);

        $database = (new DatabaseFactory())($container);

        $this->assertInstanceOf(DatabaseInterface::class, $database);
        $this->assertSame($manager->database(), $database);
    }

    public function testFailsWhenDbalServiceIsMissing(): void
    {
        $container = $this->createContainer([]);

        $this->expectException(MissingContainerServiceException::class);
        $this->expectExceptionMessage('dbal');

        (new DatabaseFactory())($container);
    }

    public function testFailsWhenDbalServiceHasWrongType(): void
    {
        $container = $this->createContainer([
            'dbal' => new stdClass(),
        ]);

        $this->expectException(InvalidContainerServiceException::class);

        (new DatabaseFactory())($container);
    }

    private function createManager(): DatabaseManager
    {
        return new DatabaseManager(new DatabaseConfig([
            'default'     => 'default',
            'databases'   => [
                'default'   => [
                    'connection' => 'primary',
                ],
                'analytics' => [
                    'connection' => 'reporting',
                ],
            ],
            'connections' => [
                'primary'   => new SQLiteDriverConfig(),
                'reporting' => new SQLiteDriverConfig(),
            ],
        ]));
    }

    /**
     * @param array<string, mixed> $services
     */
    private function createContainer(array $services): ContainerInterface
    {
        return new class($services) implements ContainerInterface {
            /**
             * @param array<string, mixed> $services
             */
            public function __construct(private readonly array $services) {}

            public function get(string $id): mixed
            {
                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }
        };
    }
}
