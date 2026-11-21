<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Sirix\ContainerResolver\Exception\InvalidContainerServiceException;
use Sirix\ContainerResolver\Exception\MissingContainerServiceException;
use Sirix\Cycle\Factory\NamedDatabaseAbstractFactory;
use stdClass;

use function array_key_exists;

final class NamedDatabaseAbstractFactoryTest extends TestCase
{
    private DatabaseManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new DatabaseManager(new DatabaseConfig([
            'default'     => 'default',
            'databases'   => [
                'default'   => [
                    'connection' => 'primary',
                ],
                'analytics' => [
                    'connection' => 'reporting',
                ],
                'archive'   => [
                    'connection' => 'archive',
                ],
            ],
            'connections' => [
                'primary'   => new SQLiteDriverConfig(),
                'reporting' => new SQLiteDriverConfig(),
                'archive'   => new SQLiteDriverConfig(),
            ],
        ]));
    }

    /**
     * laminas-servicemanager 3.x declares $requestedName without a type; narrowing it is a fatal error on class load.
     */
    #[DataProvider('serviceManagerMethods')]
    public function testRequestedNameStaysUntypedForServiceManager3(string $method): void
    {
        $parameter = (new ReflectionMethod(NamedDatabaseAbstractFactory::class, $method))->getParameters()[1];

        $this->assertSame('requestedName', $parameter->getName());
        $this->assertFalse($parameter->hasType());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function serviceManagerMethods(): iterable
    {
        yield 'canCreate' => ['canCreate'];

        yield '__invoke' => ['__invoke'];
    }

    public function testCanCreateOnlyListedServiceIds(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => $this->createConfig(),
            'dbal'   => $this->manager,
        ]);

        $this->assertTrue($factory->canCreate($container, 'db.analytics'));
        $this->assertTrue($factory->canCreate($container, 'db.archive'));
        $this->assertFalse($factory->canCreate($container, 'db.unknown'));
        $this->assertFalse($factory->canCreate($container, DatabaseInterface::class));
    }

    public function testCreatesNamedDatabasesOfTheSharedManager(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => $this->createConfig(),
            'dbal'   => $this->manager,
        ]);

        $analytics = $factory($container, 'db.analytics');
        $archive   = $factory($container, 'db.archive');

        $this->assertInstanceOf(DatabaseInterface::class, $analytics);
        $this->assertSame($this->manager->database('analytics'), $analytics);
        $this->assertSame($this->manager->database('archive'), $archive);
        $this->assertNotSame($analytics, $archive);
    }

    public function testFailsForUnknownServiceId(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => $this->createConfig(),
            'dbal'   => $this->manager,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('db.unknown');

        $factory($container, 'db.unknown');
    }

    public function testFailsWhenDatabaseNameIsInvalid(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => [
                'cycle' => [
                    'database_services' => [
                        'db.broken' => 123,
                    ],
                ],
            ],
            'dbal'   => $this->manager,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('db.broken');

        $factory($container, 'db.broken');
    }

    public function testFailsWhenDbalServiceIsMissing(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => $this->createConfig(),
        ]);

        $this->expectException(MissingContainerServiceException::class);

        $factory($container, 'db.analytics');
    }

    public function testFailsWhenDbalServiceHasWrongType(): void
    {
        $factory   = new NamedDatabaseAbstractFactory();
        $container = $this->createContainer([
            'config' => $this->createConfig(),
            'dbal'   => new stdClass(),
        ]);

        $this->expectException(InvalidContainerServiceException::class);

        $factory($container, 'db.analytics');
    }

    /**
     * @return array<string, mixed>
     */
    private function createConfig(): array
    {
        return [
            'cycle' => [
                'database_services' => [
                    'db.analytics' => 'analytics',
                    'db.archive'   => 'archive',
                ],
            ],
        ];
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
