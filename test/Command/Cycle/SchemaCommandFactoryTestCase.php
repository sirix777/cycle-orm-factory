<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use Sirix\ContainerResolver\Exception\InvalidConfigValueException;
use Sirix\ContainerResolver\Exception\MissingContainerServiceException;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Internal\FileSystem;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function bin2hex;
use function chdir;
use function file_exists;
use function getcwd;
use function in_array;
use function mkdir;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

abstract class SchemaCommandFactoryTestCase extends TestCase
{
    protected MockObject|SchemaCompilerInterface $schemaCompiler;
    protected CompiledSchemaStorage $storage;
    protected DatabaseManager $dbal;

    private string $tmpDir;
    private string $schemaPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sprintf('%s/cycle_schema_factory_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        mkdir($this->tmpDir, 0o777, true);
        $this->schemaPath = $this->tmpDir . '/schema.php';

        $this->schemaCompiler = $this->createMock(SchemaCompilerInterface::class);
        $this->storage        = new CompiledSchemaStorage();
        $this->dbal           = new DatabaseManager(new DatabaseConfig([]));
    }

    protected function tearDown(): void
    {
        (new FileSystem())->remove($this->tmpDir);

        parent::tearDown();
    }

    public function testFactoryBuildsCommand(): void
    {
        $command = $this->buildCommand($this->validConfig(true, $this->schemaPath));

        $this->assertInstanceOf($this->commandClass(), $command);
    }

    public function testFactoryPreservesConcreteReturnType(): void
    {
        $method     = new ReflectionMethod($this->factoryClass(), '__invoke');
        $returnType = $method->getReturnType();

        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame($this->commandClass(), $returnType->getName());
    }

    public function testFactoryReportsConcreteContextWhenCompilerMissing(): void
    {
        try {
            $this->buildCommandWithContainer($this->createMock(ContainerInterface::class));
            $this->fail('Expected a missing compiler service exception.');
        } catch (MissingContainerServiceException $exception) {
            $this->assertStringContainsString(SchemaCompilerInterface::class, $exception->getMessage());
            $this->assertStringContainsString($this->factoryClass(), $exception->getMessage());
        }
    }

    public function testFactoryPassesConfiguredArgumentsToCommand(): void
    {
        $schema = [
            'foo' => 'bar',
        ];

        $this->schemaCompiler
            ->expects($this->once())
            ->method($this->operationMethod())
            ->with($this->dbal, ['src/Entity'], [
                'foo' => [
                    'bar' => 'baz',
                ],
            ], ['my.generator.service'])
            ->willReturn($schema)
        ;

        $command = $this->buildCommand($this->validConfig(true, $this->schemaPath));

        $tester = new CommandTester($command);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertTrue(file_exists($this->schemaPath));
        $this->assertSame($schema, $this->storage->load($this->schemaPath));
    }

    public function testSavingBehaviorWhenCacheDisabled(): void
    {
        $this->schemaCompiler
            ->method($this->operationMethod())
            ->willReturn([
                'foo' => 'bar',
            ])
        ;

        $command = $this->buildCommand($this->validConfig(false, $this->schemaPath));

        $tester = new CommandTester($command);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));

        if ($this->savesWhenCacheDisabled()) {
            $this->assertTrue(file_exists($this->schemaPath));
        } else {
            $this->assertFalse(file_exists($this->schemaPath));
        }
    }

    public function testFactoryUsesDefaultConfiguration(): void
    {
        $schema = [
            'foo' => 'bar',
        ];

        $this->schemaCompiler
            ->expects($this->once())
            ->method($this->operationMethod())
            ->with($this->dbal, [], [], [])
            ->willReturn($schema)
        ;

        $previous = getcwd();
        $this->assertNotFalse($previous);
        chdir($this->tmpDir);

        try {
            $command = $this->buildCommand([
                'cycle' => [],
            ]);
            $tester  = new CommandTester($command);

            $this->assertSame(Command::SUCCESS, $tester->execute([]));
        } finally {
            chdir($previous);
        }

        $defaultPath = $this->tmpDir . '/' . CycleFactory::DEFAULT_COMPILED_SCHEMA_PATH;

        $this->assertTrue(file_exists($defaultPath));
        $this->assertSame($schema, $this->storage->load($defaultPath));
    }

    public function testFactoryRejectsInvalidCacheEnabledValue(): void
    {
        $config                                        = $this->validConfig(true, $this->schemaPath);
        $config['cycle']['schema']['cache']['enabled'] = 'not-a-bool';

        try {
            $this->buildCommand($config);
            $this->fail('Expected an invalid configuration value exception.');
        } catch (InvalidConfigValueException $exception) {
            $this->assertStringContainsString('cycle.schema.cache.enabled', $exception->getMessage());
            $this->assertStringContainsString($this->factoryClass(), $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function buildCommand(array $config): Command
    {
        return $this->buildCommandWithContainer($this->containerWithConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function containerWithConfig(array $config): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->method('has')
            ->willReturnCallback(static fn (string $id): bool => in_array($id, [
                'config',
                SchemaCompilerInterface::class,
                CompiledSchemaStorage::class,
                'dbal',
            ], true))
        ;

        $container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [SchemaCompilerInterface::class, $this->schemaCompiler],
                [CompiledSchemaStorage::class, $this->storage],
                ['dbal', $this->dbal],
            ])
        ;

        return $container;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validConfig(bool $cacheEnabled, string $compiledPath): array
    {
        return [
            'cycle' => [
                'entities'   => ['src/Entity'],
                'generators' => ['my.generator.service'],
                'schema'     => [
                    'cache'                             => [
                        'enabled' => $cacheEnabled,
                    ],
                    'compiled'                          => [
                        'path' => $compiledPath,
                    ],
                    'manual_mapping_schema_definitions' => [
                        'foo' => [
                            'bar' => 'baz',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return class-string
     */
    abstract protected function factoryClass(): string;

    /**
     * @return class-string
     */
    abstract protected function commandClass(): string;

    abstract protected function operationMethod(): string;

    abstract protected function savesWhenCacheDisabled(): bool;

    abstract protected function buildCommandWithContainer(ContainerInterface $container): Command;
}
