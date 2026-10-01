<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Psr\Container\ContainerInterface;
use Sirix\Cycle\Command\Cycle\SchemaSyncCommand;
use Sirix\Cycle\Command\Cycle\SchemaSyncCommandFactory;
use Symfony\Component\Console\Command\Command;

final class SchemaSyncCommandFactoryTest extends SchemaCommandFactoryTestCase
{
    protected function buildCommandWithContainer(ContainerInterface $container): Command
    {
        return (new SchemaSyncCommandFactory())($container);
    }

    protected function factoryClass(): string
    {
        return SchemaSyncCommandFactory::class;
    }

    protected function commandClass(): string
    {
        return SchemaSyncCommand::class;
    }

    protected function operationMethod(): string
    {
        return 'sync';
    }

    protected function savesWhenCacheDisabled(): bool
    {
        return false;
    }
}
