<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\Exception\ConfigException;
use Cycle\Migrations;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorWrapper;

class MigratorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigratorInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['migrator'])) {
            throw new ConfigException('Expected config migrator');
        }

        $config = $config['migrator'];

        $migratorConfig = new Migrations\Config\MigrationConfig($config);

        $dbal = $container->get('dbal');

        $migrator = new Migrations\Migrator(
            $migratorConfig,
            $dbal,
            new Migrations\FileRepository($migratorConfig)
        );

        return new MigratorWrapper($migrator);
    }
}
