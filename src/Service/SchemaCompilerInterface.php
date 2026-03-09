<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Cycle\Enum\SchemaCompileMode;

interface SchemaCompilerInterface
{
    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     * @param array<int, mixed>    $additionalGenerators
     *
     * @return array<string, mixed>
     *
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compile(
        DatabaseManager $dbal,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
        SchemaCompileMode $mode = SchemaCompileMode::Runtime,
    ): array;
}
