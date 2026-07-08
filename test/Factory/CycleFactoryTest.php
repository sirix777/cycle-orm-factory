<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\ORM;
use Cycle\ORM\SchemaInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Sirix\Cycle\Service\SchemaCompilerService;
use Sirix\Cycle\Test\Fixture\Collection\TestBaseCollection;
use Sirix\Cycle\Test\Fixture\Collection\TestCollection;
use Sirix\Cycle\Test\Fixture\Collection\TestCollectionFactory;
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
        $path   = $this->tmpDir . '/schema.php';
        $config = [
            'cycle' => [
                'entities' => ['src'],
                'schema'   => [
                    'cache'    => [
                        'enabled' => true,
                    ],
                    'compiled' => [
                        'path' => $path,
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
        $this->assertTrue(file_exists($path));
    }

    public function testFactoryCompilesWithoutPersistingWhenCacheDisabled(): void
    {
        $path   = $this->tmpDir . '/schema.php';
        $config = [
            'cycle' => [
                'entities' => ['src'],
                'schema'   => [
                    'cache'    => [
                        'enabled' => false,
                    ],
                    'compiled' => [
                        'path' => $path,
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
        $this->assertFalse(file_exists($path));
    }

    public function testFactoryAllowsMissingEntitiesWhenManualMappingExists(): void
    {
        $config = [
            'cycle' => [
                'schema' => [
                    'cache'                             => [
                        'enabled' => false,
                    ],
                    'manual_mapping_schema_definitions' => [
                        'dummy' => [
                            SchemaInterface::ENTITY      => stdClass::class,
                            SchemaInterface::DATABASE    => 'default',
                            SchemaInterface::TABLE       => 'dummy',
                            SchemaInterface::PRIMARY_KEY => 'id',
                            SchemaInterface::COLUMNS     => [
                                'id' => 'id',
                            ],
                            SchemaInterface::TYPECAST    => [
                                'id' => 'int',
                            ],
                            SchemaInterface::RELATIONS   => [],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $this->assertInstanceOf(ORM::class, $orm);
    }

    public function testFactoryConfiguresDefaultCollectionFactory(): void
    {
        $config = [
            'cycle' => [
                'entities'    => ['src'],
                'collections' => [
                    'default' => TestCollectionFactory::class,
                ],
                'schema'      => [
                    'cache' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $this->assertInstanceOf(TestCollectionFactory::class, $orm->getFactory()->collection());
    }

    public function testFactoryConfiguresNamedCollectionFactory(): void
    {
        $config = [
            'cycle' => [
                'entities'    => ['src'],
                'collections' => [
                    'factories' => [
                        'test' => TestCollectionFactory::class,
                    ],
                ],
                'schema'      => [
                    'cache' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        $container = $this->createContainer($config);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $this->assertInstanceOf(TestCollectionFactory::class, $orm->getFactory()->collection('test'));
    }

    public function testFactoryConfiguresCollectionFactoryInterfaceMatching(): void
    {
        $config = [
            'cycle' => [
                'entities'    => ['src'],
                'collections' => [
                    'factories' => [
                        'test' => [
                            'factory'   => 'test.collection.factory',
                            'interface' => TestBaseCollection::class,
                        ],
                    ],
                ],
                'schema'      => [
                    'cache' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        $collectionFactory = new TestCollectionFactory();
        $container         = $this->createContainer($config, [
            'test.collection.factory' => $collectionFactory,
        ]);

        $factory = new CycleFactory();
        $orm     = $factory($container);

        $resolvedFactory = $orm->getFactory()->collection(TestCollection::class);

        $this->assertInstanceOf(TestCollectionFactory::class, $resolvedFactory);
        $this->assertNotSame($collectionFactory, $resolvedFactory);
        $this->assertSame(TestCollection::class, $resolvedFactory->collectionClass);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $services
     */
    private function createContainer(array $config, array $services = []): ContainerInterface
    {
        $services = [
            'config' => $config,
            'dbal'   => new DatabaseManager(new DatabaseConfig([])),
            ...$services,
        ];

        return new class($services) implements ContainerInterface {
            private CompiledSchemaStorage $compiledSchemaStorage;

            /**
             * @param array<string, mixed> $services
             */
            public function __construct(private readonly array $services)
            {
                $this->compiledSchemaStorage = new CompiledSchemaStorage();
            }

            public function get(string $id)
            {
                if (SchemaCompilerInterface::class === $id) {
                    return $this->services[$id] ?? new SchemaCompilerService($this);
                }

                if (CompiledSchemaStorage::class === $id) {
                    return $this->services[$id] ?? $this->compiledSchemaStorage;
                }

                return $this->services[$id] ?? throw new RuntimeException('Unknown service: ' . $id);
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id])
                    || SchemaCompilerInterface::class === $id
                    || CompiledSchemaStorage::class === $id;
            }
        };
    }
}
