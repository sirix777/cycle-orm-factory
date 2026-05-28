<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\ORM;
use Cycle\ORM\SchemaInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Sirix\Cycle\Factory\CycleFactory;
use stdClass;

use function bin2hex;
use function file_exists;
use function is_file;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class CycleFactoryTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_factory_test_%s', sys_get_temp_dir(), bin2hex(random_bytes(8)));
        mkdir($this->tmpDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->tmpDir . '/schema.php')) {
            unlink($this->tmpDir . '/schema.php');
        }

        @rmdir($this->tmpDir);
    }

    public function testFactoryWithoutConfigThrowsException(): void
    {
        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        $factory = new CycleFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('dbal');
        $factory($container);
    }

    public function testFactoryThrowsWhenDbalMissing(): void
    {
        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                if ('config' === $id) {
                    return [
                        'cycle' => [
                            'entities' => ['src'],
                        ],
                    ];
                }

                throw new RuntimeException('Unknown service: ' . $id);
            }

            public function has(string $id): bool
            {
                return 'config' === $id;
            }
        };

        $factory = new CycleFactory();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('dbal');
        $factory($container);
    }

    public function testFactoryCompilesAndPersistsSchemaWhenCacheEnabled(): void
    {
        $path = $this->tmpDir . '/schema.php';
        $config = [
            'cycle' => [
                'entities' => ['src'],
                'schema' => [
                    'cache' => ['enabled' => true],
                    'compiled' => ['path' => $path],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
        $this->assertTrue(file_exists($path));
    }

    public function testFactoryCompilesWithoutPersistingWhenCacheDisabled(): void
    {
        $path = $this->tmpDir . '/schema.php';
        $config = [
            'cycle' => [
                'entities' => ['src'],
                'schema' => [
                    'cache' => ['enabled' => false],
                    'compiled' => ['path' => $path],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
        $this->assertFalse(file_exists($path));
    }

    public function testFactoryAllowsMissingEntitiesWhenManualMappingExists(): void
    {
        $config = [
            'cycle' => [
                'schema' => [
                    'cache' => ['enabled' => false],
                    'manual_mapping_schema_definitions' => [
                        'dummy' => [
                            SchemaInterface::ENTITY => stdClass::class,
                            SchemaInterface::DATABASE => 'default',
                            SchemaInterface::TABLE => 'dummy',
                            SchemaInterface::PRIMARY_KEY => 'id',
                            SchemaInterface::COLUMNS => ['id' => 'id'],
                            SchemaInterface::TYPECAST => ['id' => 'int'],
                            SchemaInterface::RELATIONS => [],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createContainer(array $config): ContainerInterface
    {
        $dbal = new DatabaseManager(new DatabaseConfig([]));

        return new class($config, $dbal) implements ContainerInterface {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(private readonly array $config, private readonly DatabaseManager $dbal) {}

            public function get(string $id)
            {
                return match ($id) {
                    'config' => $this->config,
                    'dbal' => $this->dbal,
                    default => throw new RuntimeException('Unknown service: ' . $id),
                };
            }

            public function has(string $id): bool
            {
                return 'config' === $id || 'dbal' === $id;
            }
        };
    }
}
