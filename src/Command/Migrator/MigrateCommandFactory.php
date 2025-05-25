<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Service\MigratorService;

final class MigrateCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigrateCommand
    {
        $migratorService = $container->get(MigratorService::class);

        return new MigrateCommand($migratorService);
    }
}
