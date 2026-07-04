<?php

declare(strict_types=1);

namespace Sirix\Cycle\Service;

use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
        DatabaseManager $databaseManager,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
    ): array;

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
    public function sync(
        DatabaseManager $databaseManager,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
    ): array;

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
    public function generateMigrations(
        DatabaseManager $databaseManager,
        array $entities,
        array $manualMappingSchemaDefinitions,
        array $additionalGenerators = [],
    ): array;
}
