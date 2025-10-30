<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Cycle\Database\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Service\MigratorService;

final class SeedCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SeedCommand
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        if (! isset($config['cycle']['migrator']['seed_directory'])) {
            throw new ConfigException('Expected config migrator with seed_directory');
        }

        $seedDirectory = $config['cycle']['migrator']['seed_directory'];
        $migratorService = $container->get(MigratorService::class);
        $dbal = $container->get('dbal');

        return new SeedCommand($migratorService, $seedDirectory, $dbal);
    }
}
