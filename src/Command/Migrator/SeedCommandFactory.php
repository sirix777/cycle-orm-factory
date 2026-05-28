<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Cycle\Database\DatabaseProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Service\MigratorService;

final class SeedCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): SeedCommand
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader = ConfigReader::fromContainer($containerResolver);
        $seedDirectory = $configReader->requiredNonEmptyString('cycle.migrator.seed_directory');

        return new SeedCommand(
            $containerResolver->get(MigratorService::class),
            $seedDirectory,
            $containerResolver->getAs('dbal', DatabaseProviderInterface::class),
        );
    }
}
