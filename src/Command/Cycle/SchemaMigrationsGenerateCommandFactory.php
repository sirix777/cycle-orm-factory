<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Cycle\Internal\SchemaCommandFactory;

final class SchemaMigrationsGenerateCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): SchemaMigrationsGenerateCommand
    {
        return SchemaCommandFactory::create($container, self::class, SchemaMigrationsGenerateCommand::class);
    }
}
