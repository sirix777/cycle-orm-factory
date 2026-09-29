<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;

final class DatabaseFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): DatabaseInterface
    {
        $databaseManager = ContainerResolver::forFactory($container, self::class)
            ->getAs('dbal', DatabaseManager::class)
        ;

        return $databaseManager->database();
    }
}
