# Mezzio Cycle ORM Factory

[![Latest Stable Version](http://poser.pugx.org/sirix/cycle-orm-factory/v)](https://packagist.org/packages/sirix/cycle-orm-factory)
[![Total Downloads](http://poser.pugx.org/sirix/cycle-orm-factory/downloads)](https://packagist.org/packages/sirix/cycle-orm-factory)
[![Latest Unstable Version](http://poser.pugx.org/sirix/cycle-orm-factory/v/unstable)](https://packagist.org/packages/sirix/cycle-orm-factory)
[![License](http://poser.pugx.org/sirix/cycle-orm-factory/license)](https://packagist.org/packages/sirix/cycle-orm-factory)
[![PHP Version Require](http://poser.pugx.org/sirix/cycle-orm-factory/require/php)](https://packagist.org/packages/sirix/cycle-orm-factory)

Migration guides:
- [v1 to v2](docs/v1-to-v2.md)
- [v2 to v3](docs/v2-to-v3.md)
- [v3 to v4](docs/v3-to-v4.md)

Factories for integrating Cycle ORM into Mezzio with a runtime-focused schema pipeline.

## Installation

```bash
composer require sirix/cycle-orm-factory
```

Optional packages:
- `symfony/console`: required for built-in CLI commands.
- `laminas/laminas-cli`: optional CLI integration for Mezzio/Laminas.
- `cycle/migrations`: required for migration runtime commands.
- `cycle/schema-migrations-generator`: required for `cycle:schema:migration:generate`.
- `cycle/entity-behavior` and `cycle/entity-behavior-uuid`: optional behavior events; runtime falls back to default Cycle command generator if not installed.

## Configuration

Create `config/autoload/cycle-orm.global.php`:

```php
<?php

declare(strict_types=1);

use Cycle\Database\Config;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;

return [
    'cycle' => [
        'db-config' => [
            'default' => 'default',
            'databases' => [
                'default' => [
                    'connection' => 'mysql',
                ],
            ],
            'connections' => [
                'mysql' => new Config\MySQLDriverConfig(
                    connection: new Config\MySQL\TcpConnectionConfig(
                        database: 'cycle-orm',
                        host: '127.0.0.1',
                        port: 3306,
                        user: 'cycle',
                        password: 'password',
                    ),
                    reconnect: true,
                    timezone: 'UTC',
                    queryCache: true,
                ),
            ],
        ],

        'migrator' => [
            'directory' => 'db/migrations',
            'table' => 'migrations',
            'seed_directory' => 'db/seeds',
            'namespace' => 'App\\Migrations', // optional
            'vendor_directories' => ['vendor/path'], // optional
            'safe' => false, // optional
        ],

        'entities' => [
            'src/App/src/Entity',
        ],

        'generators' => [
            // 'my.custom.generator.service',
            // \App\Cycle\Schema\Generator\MyCustomGenerator::class,
            // new \App\Cycle\Schema\Generator\InlineGenerator(),
        ],

        'schema' => [
            'cache' => [
                'enabled' => true,
            ],
            'compiled' => [
                'path' => 'data/cache/cycle/schema.php',
            ],
            'manual_mapping_schema_definitions' => [
                'user' => [
                    SchemaInterface::ENTITY => User::class,
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'user',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => [
                        'id' => 'id',
                        'email' => 'email',
                    ],
                    SchemaInterface::TYPECAST => [
                        'id' => 'int',
                    ],
                    SchemaInterface::RELATIONS => [
                        'profile' => [
                            Relation::TYPE => Relation::HAS_ONE,
                            Relation::TARGET => 'profile',
                            Relation::SCHEMA => [
                                Relation::CASCADE => true,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'user_id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

## Runtime schema contract (v3)

Runtime behavior is controlled by `cycle.schema.cache.enabled`.

When `true`:
- ORM tries to load compiled schema from `cycle.schema.compiled.path`.
- If file exists: schema is loaded via `require` and ORM is created.
- If file is missing: schema is compiled on first start, persisted to file, then reused.

When `false`:
- Schema is compiled on every start in memory.
- No compiled schema file is read or written by runtime.

Recommended production setup:
- keep `cache.enabled=true`
- run `cycle:schema:compile` during build/release.

## Additional schema generators

`cycle.generators` supports:
- service ID from container,
- generator FQCN with zero-arg constructor,
- direct instance implementing `Cycle\Schema\GeneratorInterface`.

Invalid entries throw `Cycle\ORM\Exception\ConfigException`.

## Manual mapping key compatibility

Primary key is `cycle.schema.manual_mapping_schema_definitions`.

For migration compatibility, the package also reads deprecated legacy key:
- `cycle.schema.manual_entity_schema_definition`

This legacy key will be removed in `4.0`. Use the new key for all new configs.

## Services

Aliases provided by `ConfigProvider`:
- `orm` -> `Cycle\ORM\ORMInterface`
- `dbal` -> `Cycle\Database\DatabaseInterface`

Migration aliases provided only when `cycle/migrations` is installed:
- `migrator` -> `Sirix\Cycle\Service\MigratorInterface`

## CLI commands

Commands are registered only when `symfony/console` is installed.

`cycle:schema:*` commands:
- `cycle:schema:compile`: compile schema and store compiled file.
- `cycle:schema:sync`: run sync pipeline; refresh compiled file only when cache is enabled.
- `cycle:schema:migration:generate`: generate migrations via schema pipeline; available only with `cycle/migrations` and `cycle/schema-migrations-generator`.

Other commands:
- `cycle:cache:clear`: remove compiled schema file.
- `cycle:migration:run`
- `cycle:migration:rollback`
- `cycle:migration:create`
- `cycle:seed:create`
- `cycle:seed:run`

Migration/seed command availability:
- registered only when `cycle/migrations` is installed.

### Create migration notes

`cycle:migration:create` supports `--database` (`-b`).
Generated filename includes database alias:
- `<timestamp>_0_<counter>_<database-alias>_<migration_name_in_snake_case>.php`

### CLI usage examples

With laminas-cli:

```bash
php vendor/bin/laminas cycle:schema:compile
php vendor/bin/laminas cycle:schema:sync
php vendor/bin/laminas cycle:migration:create CreateUsers --database default
```

With standalone Symfony Console (manual command wiring in your app):

```bash
php bin/console cycle:schema:compile
```

## Performance note

Compiled schema is stored as plain PHP and loaded by `require`, which works well with OPcache and avoids PSR-6 serialization overhead in runtime hot paths.

## More

- [Cycle ORM documentation](https://cycle-orm.dev/docs)
