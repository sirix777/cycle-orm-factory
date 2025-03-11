<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Cycle\Database\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CreateMigrationCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CreateMigrationCommand
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        if (! isset($config['cycle']['migrator']['directory'])) {
            throw new ConfigException('Expected config migrator');
        }

        $migrationDirectory = $config['cycle']['migrator']['directory'];

        return new CreateMigrationCommand($migrationDirectory);
    }
}
