<?php

declare(strict_types=1);

namespace Sirix\Cycle;

use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\MigratorFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli'  => $this->getCliConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'invokables' => [],
            'factories'  => [
                'orm'                                   => CycleFactory::class,
                'migrator'                              => MigratorFactory::class,
                'dbal'                                  => DbalFactory::class,
                Service\MigratorService::class          => Service\MigratorServiceFactory::class,
                Command\Migrator\MigrateCommand::class  => Command\Migrator\MigrateCommandFactory::class,
                Command\Migrator\RollbackCommand::class => Command\Migrator\RollbackCommandFactory::class,
            ],
        ];
    }

    private function getCliConfig(): array
    {
        return [
            'commands' => [
                'migrator:migrate'  => Command\Migrator\MigrateCommand::class,
                'migrator:rollback' => Command\Migrator\RollbackCommand::class,
            ],
        ];
    }
}
