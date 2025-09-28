<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\Exception\ConfigException;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Internal\MigrationsToggle;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorWrapper;
use Sirix\Cycle\Service\NullMigrator;

final class MigratorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigratorInterface
    {
        if (! MigrationsToggle::areMigrationsEnabled()) {
            return new NullMigrator();
        }

        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['cycle']['migrator'])) {
            throw new ConfigException('Expected config migrator');
        }

        $config = $config['cycle']['migrator'];

        $migratorConfig = new MigrationConfig($config);

        $dbal = $container->get('dbal');

        $migrator = new Migrator(
            $migratorConfig,
            $dbal,
            new FileRepository($migratorConfig)
        );

        return new MigratorWrapper($migrator);
    }
}
