<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Cycle\ORM\Exception\ConfigException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Internal\FileSystem;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorWrapper;
use Sirix\Cycle\Service\SchemaCompilerService;
use stdClass;

use function bin2hex;
use function class_exists;
use function file_get_contents;
use function glob;
use function mkdir;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

final class SchemaCompilerServiceTest extends TestCase
{
    private const string ENTITY_DIRECTORY = __DIR__ . '/../Fixture/Entity';

    private string $tmpDir;
    private DatabaseManager $dbal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_schema_compiler_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        mkdir($this->tmpDir, 0o777, true);

        $this->dbal = new DatabaseManager(new DatabaseConfig([]));
    }

    protected function tearDown(): void
    {
        (new FileSystem())->remove($this->tmpDir);

        parent::tearDown();
    }

    public function testCompileWithAdditionalGeneratorsFromContainerAndClass(): void
    {
        /** @var ContainerInterface&Stub $container */
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id): bool => 'my.generator.service' === $id);
        $container->method('get')->willReturnCallback(static function(string $id) {
            if ('my.generator.service' === $id) {
                return new ContainerDummyGenerator();
            }

            return null;
        });

        $service = new SchemaCompilerService($container);

        $schema = $service->compile(
            $this->dbal,
            [$this->tmpDir],
            [],
            [
                'my.generator.service',
                ClassDummyGenerator::class,
                new InlineDummyGenerator(),
            ],
        );

        $this->addToAssertionCount(1);
    }

    public function testCompileThrowsForInvalidAdditionalGenerator(): void
    {
        /** @var ContainerInterface&Stub $container */
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid schema generator provided in config.cycle.generators');

        $service->compile($this->dbal, [$this->tmpDir], [], ['Definitely\Missing\Generator']);
    }

    public function testCompileAllowsOnlyAdditionalGeneratorsWithoutEntitiesAndManualMapping(): void
    {
        /** @var ContainerInterface&Stub $container */
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $service = new SchemaCompilerService($container);
        $service->compile($this->dbal, [], [], [InlineDummyGenerator::class]);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGenerateMigrationsFailsWhenMigrationsAreUnavailable(): void
    {
        /** @var ContainerInterface&Stub $container */
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        if (
            ! class_exists('Cycle\Migrations\Migrator')
            || ! class_exists('Cycle\Schema\Generator\Migrations\GenerateMigrations')
        ) {
            $this->expectExceptionMessage('Schema migrations generator is unavailable');
        } else {
            $this->expectExceptionMessage('Service "migrator" is not registered.');
        }

        $service->generateMigrations($this->dbal, [$this->tmpDir], []);
    }

    public function testSyncCreatesEntityTablesInDatabase(): void
    {
        $dbal    = $this->sqliteDbal();
        $service = new SchemaCompilerService($this->createStub(ContainerInterface::class));

        $schema = $service->sync($dbal, [self::ENTITY_DIRECTORY], []);

        $this->assertArrayHasKey('widget', $schema);
        $this->assertTrue($dbal->database()->hasTable('widgets'));
        $this->assertTrue($dbal->database()->table('widgets')->hasColumn('name'));
    }

    public function testCompileDoesNotTouchDatabase(): void
    {
        $dbal    = $this->sqliteDbal();
        $service = new SchemaCompilerService($this->createStub(ContainerInterface::class));

        $schema = $service->compile($dbal, [self::ENTITY_DIRECTORY], []);

        $this->assertArrayHasKey('widget', $schema);
        $this->assertFalse($dbal->database()->hasTable('widgets'));
    }

    public function testManualMappingOverridesCompiledRole(): void
    {
        $service = new SchemaCompilerService($this->createStub(ContainerInterface::class));
        $manual  = [
            'widget' => [
                'manual' => true,
            ],
        ];

        $schema = $service->compile($this->sqliteDbal(), [self::ENTITY_DIRECTORY], $manual);

        $this->assertSame($manual['widget'], $schema['widget']);
    }

    public function testCompileThrowsWhenNothingIsConfigured(): void
    {
        $service = new SchemaCompilerService($this->createStub(ContainerInterface::class));

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Expected at least one entity directory');

        $service->compile($this->dbal, [''], [], []);
    }

    public function testCompileThrowsWhenContainerGeneratorIsNotAGenerator(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn(new stdClass());

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid schema generator provided in config.cycle.generators');

        $service->compile($this->dbal, [], [], ['not.a.generator']);
    }

    public function testGenerateMigrationsThrowsWhenMigratorHasWrongType(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id): bool => 'migrator' === $id);
        $container->method('get')->willReturn(new stdClass());

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Service "migrator" must implement %s', MigratorInterface::class));

        $service->generateMigrations($this->dbal, [self::ENTITY_DIRECTORY], []);
    }

    public function testGenerateMigrationsWritesMigrationForNewEntity(): void
    {
        $dbal     = $this->sqliteDbal();
        $config   = new MigrationConfig([
            'directory' => $this->tmpDir,
            'table'     => 'migrations',
            'namespace' => 'Migration',
        ]);
        $migrator = new MigratorWrapper(new Migrator($config, $dbal, new FileRepository($config)));

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id): bool => 'migrator' === $id);
        $container->method('get')->willReturn($migrator);

        $service = new SchemaCompilerService($container);
        $schema  = $service->generateMigrations($dbal, [self::ENTITY_DIRECTORY], []);

        $this->assertArrayHasKey('widget', $schema);
        $this->assertFalse($dbal->database()->hasTable('widgets'));

        $files = glob($this->tmpDir . '/*.php');
        $this->assertIsArray($files);
        $this->assertCount(1, $files);
        $this->assertStringContainsString("'widgets'", (string) file_get_contents($files[0]));
    }

    private function sqliteDbal(): DatabaseManager
    {
        return new DatabaseManager(new DatabaseConfig([
            'default'     => 'default',
            'databases'   => [
                'default' => [
                    'connection' => 'sqlite',
                ],
            ],
            'connections' => [
                'sqlite' => new SQLiteDriverConfig(),
            ],
        ]));
    }
}

final class ContainerDummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        return $registry;
    }
}

final class ClassDummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        return $registry;
    }
}

final class InlineDummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        return $registry;
    }
}
