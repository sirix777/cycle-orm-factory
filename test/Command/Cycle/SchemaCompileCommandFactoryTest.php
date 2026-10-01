<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Command\Cycle;

use Psr\Container\ContainerInterface;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommand;
use Sirix\Cycle\Command\Cycle\SchemaCompileCommandFactory;
use Symfony\Component\Console\Command\Command;

final class SchemaCompileCommandFactoryTest extends SchemaCommandFactoryTestCase
{
    protected function buildCommandWithContainer(ContainerInterface $container): Command
    {
        return (new SchemaCompileCommandFactory())($container);
    }

    protected function factoryClass(): string
    {
        return SchemaCompileCommandFactory::class;
    }

    protected function commandClass(): string
    {
        return SchemaCompileCommand::class;
    }

    protected function operationMethod(): string
    {
        return 'compile';
    }

    protected function savesWhenCacheDisabled(): bool
    {
        return true;
    }
}
