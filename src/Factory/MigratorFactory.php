<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Service\MigratorInterface;
use Sirix\Cycle\Service\MigratorWrapper;

final class MigratorFactory
{
    private const MIGRATION_CONFIG_MAP = [
        'directory' => 'directory',
        'vendor_directories' => 'vendorDirectories',
        'table' => 'table',
        'safe' => 'safe',
        'namespace' => 'namespace',
    ];

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): MigratorInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader = ConfigReader::fromContainer($containerResolver);
        $migrationConfig = new MigrationConfig($this->parseConfig(
            $configReader->requiredMap('cycle.migrator'),
        ));
        $databaseProvider = $containerResolver->getAs('dbal', DatabaseProviderInterface::class);

        $migrator = new Migrator(
            $migrationConfig,
            $databaseProvider,
            new FileRepository($migrationConfig)
        );

        return new MigratorWrapper($migrator);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function parseConfig(array $config): array
    {
        $result = [];

        foreach ($config as $key => $value) {
            $newKey = self::MIGRATION_CONFIG_MAP[$key] ?? $key;
            $result[$newKey] = $value;
        }

        return $result;
    }
}
