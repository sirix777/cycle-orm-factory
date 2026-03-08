<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Psr\Container\ContainerInterface;

final class SchemaCompilerServiceFactory
{
    public function __invoke(ContainerInterface $container): SchemaCompilerService
    {
        return new SchemaCompilerService($container);
    }
}
