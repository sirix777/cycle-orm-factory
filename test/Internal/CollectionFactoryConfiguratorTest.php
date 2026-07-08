<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Internal;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Cycle\Internal\CollectionFactoryConfigurator;
use Sirix\Cycle\Test\Fixture\Collection\TestBaseCollection;
use Sirix\Cycle\Test\Fixture\Collection\TestCollection;
use Sirix\Cycle\Test\Fixture\Collection\TestCollectionFactory;

final class CollectionFactoryConfiguratorTest extends TestCase
{
    public function testCreatesFactoryWithDefaultCollectionFactory(): void
    {
        $factory = $this->createFactory([
            'cycle' => [
                'collections' => [
                    'default' => TestCollectionFactory::class,
                ],
            ],
        ]);

        $this->assertInstanceOf(TestCollectionFactory::class, $factory->collection());
    }

    public function testCreatesFactoryWithNamedCollectionFactory(): void
    {
        $factory = $this->createFactory([
            'cycle' => [
                'collections' => [
                    'factories' => [
                        'test' => TestCollectionFactory::class,
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(TestCollectionFactory::class, $factory->collection('test'));
    }

    public function testCreatesFactoryWithCollectionClassMatching(): void
    {
        $collectionFactory = new TestCollectionFactory();
        $factory           = $this->createFactory(
            [
                'cycle' => [
                    'collections' => [
                        'factories' => [
                            'test' => [
                                'factory'   => 'test.collection.factory',
                                'interface' => TestBaseCollection::class,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'test.collection.factory' => $collectionFactory,
            ],
        );

        $resolvedFactory = $factory->collection(TestCollection::class);

        $this->assertInstanceOf(TestCollectionFactory::class, $resolvedFactory);
        $this->assertNotSame($collectionFactory, $resolvedFactory);
        $this->assertSame(TestCollection::class, $resolvedFactory->collectionClass);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $services
     */
    private function createFactory(array $config, array $services = []): Factory
    {
        $services = [
            'config' => $config,
            ...$services,
        ];

        $container = new class($services) implements ContainerInterface {
            /**
             * @param array<string, mixed> $services
             */
            public function __construct(private readonly array $services) {}

            public function get(string $id)
            {
                return $this->services[$id] ?? throw new RuntimeException('Unknown service: ' . $id);
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };

        return (new CollectionFactoryConfigurator())->createFactory(
            new DatabaseManager(new DatabaseConfig([])),
            ConfigReader::fromContainer(ContainerResolver::forContext($container, self::class)),
            ContainerResolver::forContext($container, self::class),
        );
    }
}
