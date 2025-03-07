<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\Config;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class DbalFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): DatabaseManager
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['cycle']['db-config'])) {
            throw new ConfigException('Expected config databases');
        }

        $config = $config['cycle']['db-config'];

        return new DatabaseManager(new Config\DatabaseConfig($config));
    }
}
