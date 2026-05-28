<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Service\MigratorService;

final class RollbackCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): RollbackCommand
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new RollbackCommand($containerResolver->get(MigratorService::class));
    }
}
