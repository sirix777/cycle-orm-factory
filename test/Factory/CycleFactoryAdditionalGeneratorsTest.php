<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;
use Sirix\Cycle\Enum\SchemaProperty;
use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Service\MigratorInterface;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

use function bin2hex;
use function class_exists;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;

final class CycleFactoryAdditionalGeneratorsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/cycle_factory_additional_generators_' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @rmdir($this->tmpDir);
    }

    /**
     * @throws ReflectionException
     */
    public function testAdditionalGenerators(): void
    {
        $finder = (new Finder())->files()->in([$this->tmpDir]);
        $classLocator = new ClassLocator($finder);

        $factory = new CycleFactory();
        $method = new ReflectionMethod(CycleFactory::class, 'getSchemaGenerators');

        $migrator = $this->createMock(MigratorInterface::class);

        // container resolves one generator by service id and leaves one to be instantiated by FQCN
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static function(string $id): bool {
            return 'my.generator.service' === $id;
        });
        $container->method('get')->willReturnCallback(static function(string $id) {
            if ('my.generator.service' === $id) {
                return new ContainerDummyGenerator();
            }

            return null;
        });

        // Ensure the class exists (it is declared below in this file)
        $fqcn = DummyGenerator::class;
        $this->assertTrue(class_exists($fqcn));

        /** @var array<object> $generators */
        $generators = $method->invoke(
            $factory,
            $container,
            $classLocator,
            $migrator,
            [
                'my.generator.service',
                $fqcn,
                new InitializedDummyGenerator(),
            ],
            SchemaProperty::SyncTables,
        );

        $this->assertTrue($this->containsClass($generators, DummyGenerator::class));
        $this->assertTrue($this->containsClass($generators, ContainerDummyGenerator::class));
        $this->assertTrue($this->containsClass($generators, InitializedDummyGenerator::class));
    }

    /**
     * @param array<object> $objects
     */
    private function containsClass(array $objects, string $fqcn): bool
    {
        foreach ($objects as $o) {
            if ($o::class === $fqcn) {
                return true;
            }
        }

        return false;
    }
}

final class DummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        // No changes, this is just a stub for testing that it can be added
        return $registry;
    }
}

final class ContainerDummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        // No changes, this is just a stub for testing that it can be added
        return $registry;
    }
}

final class InitializedDummyGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        // No changes, this is just a stub for testing that it can be added
        return $registry;
    }
}
