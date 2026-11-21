<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Migrator;

use PHPUnit\Framework\TestCase;
use Sirix\ContainerResolver\Exception\MissingConfigValueException;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommand;
use Sirix\Cycle\Command\Migrator\CreateMigrationCommandFactory;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Test\Support\ArrayContainer;

final class CreateMigrationCommandFactoryTest extends TestCase
{
    public function testRequiresMigratorDirectory(): void
    {
        $this->expectException(MissingConfigValueException::class);
        $this->expectExceptionMessage('cycle.migrator.directory');

        (new CreateMigrationCommandFactory())(new ArrayContainer([
            'config'                 => [
                'cycle' => [],
            ],
            MigratorInterface::class => $this->createStub(MigratorInterface::class),
        ]));
    }

    public function testBuildsCommand(): void
    {
        $command = (new CreateMigrationCommandFactory())(new ArrayContainer([
            'config'                 => [
                'cycle' => [
                    'migrator' => [
                        'directory' => 'data/migrations',
                    ],
                ],
            ],
            MigratorInterface::class => $this->createStub(MigratorInterface::class),
        ]));

        $this->assertInstanceOf(CreateMigrationCommand::class, $command);
    }
}
