<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Psr\Container\ContainerInterface;
use Sirix\Cycle\Command\Cycle\SchemaMigrationsGenerateCommand;
use Sirix\Cycle\Command\Cycle\SchemaMigrationsGenerateCommandFactory;
use Symfony\Component\Console\Command\Command;

final class SchemaMigrationsGenerateCommandFactoryTest extends SchemaCommandFactoryTestCase
{
    protected function buildCommandWithContainer(ContainerInterface $container): Command
    {
        return (new SchemaMigrationsGenerateCommandFactory())($container);
    }

    protected function factoryClass(): string
    {
        return SchemaMigrationsGenerateCommandFactory::class;
    }

    protected function commandClass(): string
    {
        return SchemaMigrationsGenerateCommand::class;
    }

    protected function operationMethod(): string
    {
        return 'generateMigrations';
    }

    protected function savesWhenCacheDisabled(): bool
    {
        return false;
    }
}
