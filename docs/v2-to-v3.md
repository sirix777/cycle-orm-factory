# Migration Guide: Cycle ORM Factory v2 to v3

This guide describes breaking changes and migration steps for upgrading to v3.

## Breaking changes summary

| Area | v2 | v3 |
|---|---|---|
| Schema cache backend | PSR-6 (`Psr\Cache\CacheItemPoolInterface`) | File-based compiled schema (`require` from `schema.compiled.path`) |
| Runtime schema mode | `SchemaProperty` (`SyncTables` / `GenerateMigrations`) in config | Removed; mode moved to explicit CLI commands |
| Schema config keys | `schema.cache.key`, `schema.cache.service`, `schema.property` | Removed |
| Runtime behavior | PSR-6 cache lookup + compile pipeline | File load/compile depending on `schema.cache.enabled` |
| Commands | no `cycle:schema:*` flow | `cycle:schema:compile`, `cycle:schema:sync`, `cycle:schema:migration:generate` |
| Console dependency | direct dependency | optional dependency (`symfony/console`) |

## Configuration migration

### Before (v2)

```php
use Psr\Cache\CacheItemPoolInterface;
use Sirix\Cycle\Enum\SchemaProperty;

return [
    'cycle' => [
        'schema' => [
            'property' => SchemaProperty::GenerateMigrations,
            'cache' => [
                'enabled' => true,
                'key' => 'cycle_cached_schema',
                'service' => CacheItemPoolInterface::class,
            ],
        ],
    ],
];
```

### After (v3)

```php
return [
    'cycle' => [
        'schema' => [
            'cache' => [
                'enabled' => true,
            ],
            'compiled' => [
                'path' => 'data/cache/cycle/schema.php',
            ],
            'manual_mapping_schema_definitions' => [
                // optional manual schema chunks
            ],
        ],
    ],
];
```

Removed keys:
- `cycle.schema.property`
- `cycle.schema.cache.key`
- `cycle.schema.cache.service`

New key:
- `cycle.schema.compiled.path`

## Runtime behavior in v3

When `cycle.schema.cache.enabled=true`:
- runtime loads compiled schema from file,
- if file is missing, runtime compiles schema once and writes the file.

When `cycle.schema.cache.enabled=false`:
- runtime compiles schema in memory on every start,
- no compiled schema file read/write.

## New schema commands

- `cycle:schema:compile`
  - compile runtime schema and save compiled file.
- `cycle:schema:sync`
  - run schema sync pipeline (`SyncTables`).
- `cycle:schema:migration:generate`
  - run migration generation pipeline.
  - available only if `cycle/migrations` and `cycle/schema-migrations-generator` are installed and migrations are not disabled by `CYCLE_MIGRATIONS_DISABLED`.

## Deploy flow (recommended)

1. Install dependencies.
2. Run schema compile during build/release:
   - `php vendor/bin/laminas cycle:schema:compile`
3. Deploy artifact with compiled schema file available at `cycle.schema.compiled.path`.

Notes:
- precompile is recommended for best cold-start latency,
- runtime fallback compile still works when cache is enabled and file is missing.

## Command registration and optional dependencies

- Built-in commands are registered only when `symfony/console` is installed.
- Migration/seed commands are registered only when `cycle/migrations` is installed.
- `laminas/laminas-cli` remains optional and is only an integration layer.

## `cycle:cache:clear` in v3

Command is still available in v3:
- `cycle:cache:clear` removes compiled schema file.

Use it when forcing schema refresh after changing entities/config outside of normal build flow.

## Manual mapping compatibility bridge

Preferred key:
- `cycle.schema.manual_mapping_schema_definitions`

Temporarily supported legacy key:
- `cycle.schema.manual_entity_schema_definition`

Migrate configs to the new key.
