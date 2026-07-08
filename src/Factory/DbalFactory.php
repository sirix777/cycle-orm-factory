<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\Config;
use Cycle\Database\DatabaseManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;

final class DbalFactory
{
    /**
     * @throws ResolverException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): DatabaseManager
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new DatabaseManager(new Config\DatabaseConfig(
            $configReader->requiredMap('cycle.db-config'),
        ));
    }
}
