<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Internal\FileSystem;
use Sirix\Cycle\Service\MigratorService;
use Sirix\Cycle\Service\MigratorWrapper;

use function bin2hex;
use function mkdir;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

final class MigratorWrapperTest extends TestCase
{
    private string $directory;
    private DatabaseManager $dbal;
    private MigrationConfig $config;
    private FileRepository $repository;
    private MigratorWrapper $wrapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sprintf('%s/cycle_migrator_wrapper_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        mkdir($this->directory, 0o777, true);

        $this->dbal = new DatabaseManager(new DatabaseConfig([
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

        $this->config = new MigrationConfig([
            'directory' => $this->directory,
            'table'     => 'migrations',
            'namespace' => 'Migration',
            'safe'      => true,
        ]);
        $this->repository = new FileRepository($this->config);
        $this->wrapper    = new MigratorWrapper(new Migrator($this->config, $this->dbal, $this->repository));
    }

    protected function tearDown(): void
    {
        (new FileSystem())->remove($this->directory);

        parent::tearDown();
    }

    public function testExposesConfigAndRepository(): void
    {
        $this->assertSame($this->config, $this->wrapper->getConfig());
        $this->assertSame($this->repository, $this->wrapper->getRepository());
    }

    public function testConfigureCreatesMigrationTable(): void
    {
        $this->assertFalse($this->wrapper->isConfigured());

        $this->wrapper->configure();

        $this->assertTrue($this->wrapper->isConfigured());
        $this->assertTrue($this->dbal->database()->hasTable('migrations'));
    }

    public function testMigrateAndRollbackRealMigrationThroughService(): void
    {
        $class = 'CreateWidgets' . bin2hex(random_bytes(4));
        $this->repository->registerMigration('create_widgets', 'Migration\\' . $class, sprintf(
            <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Migration;

                use Cycle\Migrations\Migration;

                final class %s extends Migration
                {
                    public function up(): void
                    {
                        $this->table('widgets')->addColumn('id', 'primary')->create();
                    }

                    public function down(): void
                    {
                        $this->table('widgets')->drop();
                    }
                }
                PHP,
            $class,
        ));

        $service  = new MigratorService($this->wrapper);
        $messages = [];

        $service->migrate(static function(string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame(['Migrating create_widgets'], $messages);
        $this->assertTrue($this->dbal->database()->hasTable('widgets'));
        $this->assertNull($this->wrapper->run());

        $service->rollback();

        $this->assertFalse($this->dbal->database()->hasTable('widgets'));
    }
}
