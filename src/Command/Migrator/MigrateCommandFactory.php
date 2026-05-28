<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Service\MigratorService;

final class MigrateCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): MigrateCommand
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new MigrateCommand($containerResolver->get(MigratorService::class));
    }
}
