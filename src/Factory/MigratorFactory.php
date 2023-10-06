<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\Exception\ConfigException;
use Cycle\Migrations;
use Cycle\Migrations\Migrator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class MigratorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Migrator
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['migrator'])) {
            throw new ConfigException('Expected config migrator');
        }

        $config = $config['migrator'];

        $migratorConfig = new Migrations\Config\MigrationConfig($config);

        $dbal = $container->get('dbal');

        return new Migrations\Migrator($migratorConfig, $dbal, new Migrations\FileRepository($migratorConfig));
    }
}
