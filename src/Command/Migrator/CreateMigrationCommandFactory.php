<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Service\MigratorInterface;

final class CreateMigrationCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): CreateMigrationCommand
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new CreateMigrationCommand(
            $configReader->requiredNonEmptyString('cycle.migrator.directory'),
            $containerResolver->get(MigratorInterface::class),
        );
    }
}
