<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommand;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommandFactory;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;

use function in_array;

final class SchemaCompileCommandFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private MockObject|SchemaCompilerInterface $schemaCompiler;
    private CompiledSchemaStorage $storage;
    private DatabaseManager $dbal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container      = $this->createMock(ContainerInterface::class);
        $this->schemaCompiler = $this->createMock(SchemaCompilerInterface::class);
        $this->storage        = new CompiledSchemaStorage();
        $this->dbal           = new DatabaseManager(new DatabaseConfig([]));
    }

    public function testFactoryBuildsCommand(): void
    {
        $this->container
            ->method('has')
            ->willReturnCallback(static fn (string $id): bool => in_array($id, [
                'config',
                SchemaCompilerInterface::class,
                CompiledSchemaStorage::class,
                'dbal',
            ], true))
        ;

        $this->container
            ->method('get')
            ->willReturnMap([
                [
                    'config', [
                        'cycle' => [
                            'entities'   => ['src/Entity'],
                            'generators' => ['my.generator'],
                            'schema'     => [
                                'cache'                             => [
                                    'enabled' => true,
                                ],
                                'compiled'                          => [
                                    'path' => '/tmp/schema.php',
                                ],
                                'manual_mapping_schema_definitions' => [
                                    'foo' => [
                                        'bar' => 'baz',
                                    ],
                                ],
                            ],
                        ],
                    ]],
                [SchemaCompilerInterface::class, $this->schemaCompiler],
                [CompiledSchemaStorage::class, $this->storage],
                ['dbal', $this->dbal],
            ])
        ;

        $factory = new SchemaCompileCommandFactory();
        $command = $factory($this->container);

        $this->assertInstanceOf(SchemaCompileCommand::class, $command);
    }
}
