<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Migrator;

use Cycle\Database\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class CreateSeedCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CreateSeedCommand
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        if (! isset($config['cycle']['migrator']['seed-directory'])) {
            throw new ConfigException('Expected config migrator with seed-directory');
        }

        $seedDirectory = $config['cycle']['migrator']['seed-directory'];

        return new CreateSeedCommand($seedDirectory);
    }
}
