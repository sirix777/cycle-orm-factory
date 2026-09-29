<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Integration;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\ConfigProvider;
use Sirix\Cycle\Factory\NamedDatabaseAbstractFactory;

use function class_exists;

final class DatabaseServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(ServiceManager::class)) {
            $this->markTestSkipped('Laminas ServiceManager is not installed in this environment.');
        }
    }

    public function testResolvesDefaultAndNamedDatabasesFromTheSharedManager(): void
    {
        $container = $this->createServiceManager();

        $manager  = $container->get('dbal');
        $provider = $container->get(DatabaseProviderInterface::class);
        $default  = $container->get(DatabaseInterface::class);

        $this->assertInstanceOf(DatabaseManager::class, $manager);
        $this->assertSame($manager, $provider);
        $this->assertInstanceOf(DatabaseInterface::class, $default);
        $this->assertSame($manager->database(), $default);

        $analytics = $container->get('db.analytics');
        $archive   = $container->get('db.archive');

        $this->assertSame($manager->database('analytics'), $analytics);
        $this->assertSame($manager->database('archive'), $archive);
        $this->assertNotSame($analytics, $archive);
    }

    public function testDoesNotInterceptUnknownServices(): void
    {
        $container = $this->createServiceManager();

        $this->assertFalse($container->has('db.unknown'));

        $this->expectException(ServiceNotFoundException::class);

        $container->get('db.unknown');
    }

    private function createServiceManager(): ServiceManager
    {
        $dependencies = (new ConfigProvider())->getDependencies();

        self::assertContains(NamedDatabaseAbstractFactory::class, $dependencies['abstract_factories']);

        $container = new ServiceManager($dependencies);
        $container->setService('config', [
            'cycle' => [
                'database_services' => [
                    'db.analytics' => 'analytics',
                    'db.archive'   => 'archive',
                ],
                'db-config'         => [
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
                ],
            ],
        ]);

        return $container;
    }
}
