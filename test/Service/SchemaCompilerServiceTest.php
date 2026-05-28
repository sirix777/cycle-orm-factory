<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Enum\SchemaCompileMode;
use Sirix\Cycle\Service\SchemaCompilerService;

use function bin2hex;
use function mkdir;
use function putenv;
use function random_bytes;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;

final class SchemaCompilerServiceTest extends TestCase
{
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
        putenv('CYCLE_MIGRATIONS_DISABLED');
        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    public function testCompileWithAdditionalGeneratorsFromContainerAndClass(): void
    {
        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
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
        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid schema generator provided in config.cycle.generators');

        $service->compile($this->dbal, [$this->tmpDir], [], ['Definitely\Missing\Generator']);
    }

    public function testCompileAllowsOnlyAdditionalGeneratorsWithoutEntitiesAndManualMapping(): void
    {
        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $service = new SchemaCompilerService($container);
        $service->compile($this->dbal, [], [], [InlineDummyGenerator::class]);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCompileWithMigrationGenerationFailsWhenDisabledByEnv(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED=1');

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $service = new SchemaCompilerService($container);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Schema migrations generator is unavailable');

        $service->compile($this->dbal, [$this->tmpDir], [], schemaCompileMode: SchemaCompileMode::GenerateMigrations);
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
