# Migration Guide: Cycle ORM Factory v3 to v4

This guide describes breaking changes and migration steps for upgrading to v4.

## Breaking changes summary

| Area | v3 | v4 |
|---|---|---|
| Schema compiler API | `compile(..., SchemaCompileMode $mode = SchemaCompileMode::Runtime)` | Explicit `compile(...)`, `sync(...)`, `generateMigrations(...)` methods |
| Schema compile mode enum | `Sirix\Cycle\Enum\SchemaCompileMode` | Removed |
| Migration fallback service | `NullMigrator` returned when migrations were disabled/unavailable | Removed; migrator services are not registered when `cycle/migrations` is missing |
| Migration disable flag | `CYCLE_MIGRATIONS_DISABLED` could hide migration commands | Removed |
| Migration DI registrations | Some migration services could exist as fallbacks | Migration services/aliases/commands are registered only when `cycle/migrations` is installed |
| Manual mapping schema config | `cycle.schema.manual_entity_schema_definition` legacy key was still accepted | Removed; use `cycle.schema.manual_mapping_schema_definitions` |

## Schema compiler API migration

The schema compiler no longer accepts a mode enum. Runtime compilation, schema sync, and migration generation are now separate method calls.

### Before (v3)

```php
use Sirix\Cycle\Enum\SchemaCompileMode;
use Sirix\Cycle\Service\SchemaCompilerInterface;

/** @var SchemaCompilerInterface $schemaCompiler */
$schema = $schemaCompiler->compile(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
    SchemaCompileMode::Runtime,
);

$schemaCompiler->compile(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
    SchemaCompileMode::SyncTables,
);

$schemaCompiler->compile(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
    SchemaCompileMode::GenerateMigrations,
);
```

### After (v4)

```php
use Sirix\Cycle\Service\SchemaCompilerInterface;

/** @var SchemaCompilerInterface $schemaCompiler */
$schema = $schemaCompiler->compile(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
);

$schemaCompiler->sync(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
);

$schemaCompiler->generateMigrations(
    $databaseManager,
    $entities,
    $manualMappingSchemaDefinitions,
    $additionalGenerators,
);
```

If you implement `SchemaCompilerInterface`, update your implementation to add:
- `compile(...)`
- `sync(...)`
- `generateMigrations(...)`

## Migration package availability

`NullMigrator` was removed. When `cycle/migrations` is not installed, the package no longer registers migration services or migration commands.

Services/aliases registered only when `cycle/migrations` is installed:
- `migrator`
- `Sirix\Cycle\Service\MigratorInterface`
- `Sirix\Cycle\Service\MigratorService`

Commands registered only when `cycle/migrations` is installed:
- `cycle:migration:run`
- `cycle:migration:rollback`
- `cycle:migration:create`
- `cycle:seed:create`
- `cycle:seed:run`

`cycle:schema:migration:generate` additionally requires `cycle/schema-migrations-generator`.

## Removed migration disable env flag

The `CYCLE_MIGRATIONS_DISABLED` environment variable is no longer supported.

### Before (v3)

```bash
CYCLE_MIGRATIONS_DISABLED=1 php vendor/bin/laminas
```

### After (v4)

Control migration command availability through installed packages:
- install `cycle/migrations` to enable migration/seed services and commands,
- remove `cycle/migrations` to keep the migration layer unregistered.

## Application checks

If your app previously fetched the migrator unconditionally, check service availability first or require `cycle/migrations` in the application.

```php
if ($container->has('migrator')) {
    $migrator = $container->get('migrator');
}
```

If your app needs migration commands, ensure these optional packages are installed:

```bash
composer require cycle/migrations cycle/schema-migrations-generator symfony/console laminas/laminas-cli
```

`cycle/schema-migrations-generator` is needed only for `cycle:schema:migration:generate`.

## Manual mapping schema config

The deprecated `cycle.schema.manual_entity_schema_definition` config key is no longer supported.

### Before (v3)

```php
return [
    'cycle' => [
        'schema' => [
            'manual_entity_schema_definition' => [
                // role => schema definition
            ],
        ],
    ],
];
```

### After (v4)

```php
return [
    'cycle' => [
        'schema' => [
            'manual_mapping_schema_definitions' => [
                // role => schema definition
            ],
        ],
    ],
];
```
