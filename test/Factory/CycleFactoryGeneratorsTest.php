<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\RepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
use function putenv;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;

final class CycleFactoryGeneratorsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/cycle_factory_generators_' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testGenerateMigrationsNotIncludedWhenDisabledFlag(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED=1');

        $migrator = $this->createMock(MigratorInterface::class);
        $migrator->expects($this->never())->method('getRepository');
        $migrator->expects($this->never())->method('getConfig');

        $generators = $this->invokeGetSchemaGenerators($migrator);

        foreach ($generators as $gen) {
            $this->assertNotSame('Cycle\Schema\Generator\Migrations\GenerateMigrations', $gen::class);
        }
    }

    public function testGenerateMigrationsIncludedWhenEnabledAndClassExists(): void
    {
        if (! class_exists('Cycle\Schema\Generator\Migrations\GenerateMigrations')) {
            $this->markTestSkipped('GenerateMigrations class is not available in this environment.');
        }

        putenv('CYCLE_MIGRATIONS_DISABLED=0');

        /** @var MigratorInterface|MockObject $migrator */
        $migrator = $this->createMock(MigratorInterface::class);
        $repo = $this->createMock(RepositoryInterface::class);
        $migrator->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo)
        ;
        $migrator->expects($this->once())
            ->method('getConfig')
            ->willReturn(new MigrationConfig([
                'directory' => $this->tmpDir,
                'table' => 'migrations',
            ]))
        ;

        $generators = $this->invokeGetSchemaGenerators($migrator);

        $this->assertTrue(
            $this->containsClass($generators, 'Cycle\Schema\Generator\Migrations\GenerateMigrations'),
            'Expected GenerateMigrations to be present among generators.'
        );
    }

    /**
     * @return array<object>
     *
     * @throws ReflectionException
     */
    private function invokeGetSchemaGenerators(MigratorInterface $migrator): array
    {
        $finder = (new Finder())->files()->in([$this->tmpDir]);
        $classLocator = new ClassLocator($finder);

        $factory = new CycleFactory();
        $method = new ReflectionMethod(CycleFactory::class, 'getSchemaGenerators');

        return $method->invoke($factory, $classLocator, $migrator, SchemaProperty::GenerateMigrations);
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
