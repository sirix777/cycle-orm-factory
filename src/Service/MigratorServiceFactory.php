<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;

final class MigratorServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): MigratorService
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new MigratorService($containerResolver->getAs('migrator', MigratorInterface::class));
    }
}
