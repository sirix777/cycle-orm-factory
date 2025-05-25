<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class MigratorServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigratorService
    {
        $migrator = $container->get('migrator');

        return new MigratorService($migrator);
    }
}
