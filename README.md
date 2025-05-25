# Mezzio Cycle ORM Factory

![GitHub license](https://img.shields.io/github/license/sirix777/cycle-orm-factory?style=flat-square)
<a href="https://packagist.org/packages/sirix/cycle-orm-factory">
  <img src="https://img.shields.io/packagist/dt/sirix/cycle-orm-factory?style=flat-square" alt="Total Installs">
</a>

[Migration Guide: Cycle ORM Factory v1 to v2](docs/v1-to-v2.md)

This package provides factories for integrating Cycle ORM into the Mezzio framework, providing seamless setup and configuration.
### Installation
```bash
composer require sirix/cycle-orm-factory
```

#### Requirements
- PHP 8.1, 8.2, 8.3, or 8.4

### Configuration
Create a configuration file, for example, `config/autoload/cycle-orm.global.php`:
```php
<?php

declare(strict_types=1);

use Cycle\Database\Config;
use Psr\Cache\CacheItemPoolInterface;
use Sirix\Cycle\Enum\SchemaProperty;


return [
    'cycle' => [
        'db-config' => [
            'default' => 'default',
            'databases' => [
                'default' => [
                    'connection' => 'mysql',
                ]
            ],
            'connections' => [
                'mysql' => new Config\MySQLDriverConfig(
                    connection: new Config\MySQL\TcpConnectionConfig(
                        database: 'cycle-orm',
                        host: '127.0.0.1',
                        port: 3306,
                        user: 'cycle',
                        password: 'password'
                    ),
                    reconnect: true,
                    timezone: 'UTC',
                    queryCache: true,
                ),
            ]
        ],
        'migrator' => [
            'directory' => 'db/migrations',
            'table' => 'migrations',
            'seed-directory' => 'db/seeds',
        ],
        'entities' => [
            'src/App/src/Entity', // The 'entities' configuration must always be present and point to an existing directory, even when working with manual entities.
        ],
        'schema' => [
            'property' => SchemaProperty::GenerateMigrations,
            'cache' => [
                'enabled' => true,
                'key' => 'cycle_cached_schema', // optional parameter
                'service' => CacheItemPoolInterface::class, // optional parameter if psr container have 'cache' CacheItemPoolInterface service
            ]
        ],
    ],
];
```

### Migrator Configuration
```php
'migrator' => [
    'directory'      => 'db/migrations',
    'table'          => 'migrations',
    'seed-directory' => 'db/seeds',
],
```
- `directory`: Specifies the directory where the migration files will be stored.
- `table`: Specifies the name of the table used to store migration information.

### Entities Configuration
```php
'entities' => [
    'src/App/src/Entity',
],
```
- Specifies the directory in which your entity classes are located.

### Manual mapping schema definitions

You can define manual mapping schema definitions for your entities. This is useful when you need to customize the schema for a specific entity. For example, you can define the table name, primary key, columns, typecast handlers, typecasts, and relations for an entity.

```php
<?php

/**
 * Example of a Cycle ORM schema configuration.
 * Use this template to define your entities, relationships, and database mappings.
 * Note: This configuration must be placed within the 'schema' => ['manual_mapping_schema_definitions'] array.
 */

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;

return [
    'cycle' => [
        'schema' => [
            'manual_mapping_schema_definitions' => [
                'example_entity' => [
                    // Entity class for mapping
                    SchemaInterface::ENTITY => YourEntity::class,

                    // Database and table name for the entity
                    SchemaInterface::DATABASE => 'your-database',
                    SchemaInterface::TABLE => 'your_table_name',

                    // Primary key column
                    SchemaInterface::PRIMARY_KEY => 'id',

                    // Column mappings: database columns to entity properties
                    SchemaInterface::COLUMNS => [
                        'id' => 'id',
                        'name' => 'name',
                        'createdAt' => 'created_at',
                        'updatedAt' => 'updated_at',
                    ],

                    // Typecasting for fields
                    SchemaInterface::TYPECAST => [
                        'id' => 'int',
                        'createdAt' => 'datetime',
                        'updatedAt' => 'datetime',
                    ],

                    // Optional: Custom typecast handlers
                    SchemaInterface::TYPECAST_HANDLER => YourTypecastHandler::class,

                    // Relationships definition
                    SchemaInterface::RELATIONS => [
                        'relatedEntities' => [
                            Relation::TYPE => Relation::HAS_MANY, // Relation type
                            Relation::TARGET => RelatedEntity::class, // Target entity class
                            Relation::SCHEMA => [
                                Relation::CASCADE => true, // Cascade updates/deletes
                                Relation::INNER_KEY => 'id', // Local key in this entity
                                Relation::OUTER_KEY => 'related_entity_id', // Foreign key in the related entity
                                Relation::WHERE => [
                                    'status' => 'active', // Optional filter for the relation
                                ],
                            ],
                        ],
                        'anotherEntity' => [
                            Relation::TYPE => Relation::BELONGS_TO,
                            Relation::TARGET => AnotherEntity::class,
                            Relation::SCHEMA => [
                                Relation::CASCADE => true,
                                Relation::INNER_KEY => 'another_entity_id',
                                Relation::OUTER_KEY => 'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```
See the [Cycle ORM documentation](https://cycle-orm.dev/docs/schema-manual/current/en) for more information on manual mapping schema definitions.



### Schema Configuration
```php
    'schema'   => [
        'property' => SchemaProperty::GenerateMigrations,
        'cache'    => [
            'enabled' => true,
            'key' => 'cycle_orm_cached_schema',
            'service' => CacheItemPoolInterface::class, 
        ],
    ],
```
- `property`: Configures the schema property, options include `null`, `SchemaProperty::SyncTables`, or `SchemaProperty::GenerateMigrations`.
- `cache.enabled`: Enables or disables caching of the generated schema.
- `cache.key`: Specifies the key used for storing the schema cache. This is optional and can be left empty if not
  needed.
- `cache.service`: Defines the PSR-6 `CacheItemPoolInterface` service to be used for schema caching. This allows
  integration with various caching mechanisms supported in your application. If left out, the default service (with name 'cache') from the
  container will be utilized if configured.

### Schema Property Options

`SchemaProperty::SyncTables`

The SyncTables option synchronizes the database tables based on the provided entity classes. It ensures that the tables match the structure defined in the entity classes. This can be useful during development when the database schema evolves along with your application.

`SchemaProperty::GenerateMigrations`

The GenerateMigrations option is used to automatically generate migration files based on changes detected in your entity classes. When enabled, the ORM analyzes the differences between the current database schema and the defined entities. It then generates migration files to apply these changes, making it easier to version and manage your database schema changes.

Select the appropriate SchemaProperty option based on the requirements of your project. Customize the configuration to your needs and enjoy the seamless integration of Cycle ORM with Mezzio.

* Note: The schema properties (e.g., SyncTables, GenerateMigrations) work only with annotated entities.
* Ensure that your entity classes are properly annotated to leverage these features.

### Use in your project
```php
// Access services using short aliases
$container->get('orm'); // Cycle\ORM\ORM
$container->get('dbal'); // Cycle\Database\DatabaseInterface
$container->get('migrator'); // Cycle\Migrations\Migrator

// Or access services using their interface names (aliases provided by ConfigProvider)
$container->get(Cycle\ORM\ORMInterface::class); // Same as $container->get('orm')
$container->get(Cycle\Database\DatabaseInterface::class); // Same as $container->get('dbal')
$container->get(Sirix\Cycle\Service\MigratorInterface::class); // Same as $container->get('migrator')
```

These factories provide the necessary parts to work seamlessly with Cycle ORM within the Mezzio Framework. The ConfigProvider automatically sets up aliases so you can use either the short service names or the full interface names according to your preference. Customize the configuration to meet the needs of your project.

For more information about Cycle ORM, see the [Cycle ORM documentation](https://cycle-orm.dev/docs).

## Migrator Commands
The `Sirix\Cycle\Command\Migrator` namespace provides some commands for managing database migrations and the cached Cycle ORM schema. These commands are intended for use with the `laminas-cli` tool.

### 1. `cycle:migrator:run` Command

#### Description
The `cycle:migrator:run` command performs the necessary database migration steps, applying changes specified in migration files to synchronize your database schema.

#### Usage
```bash
# Run all pending migrations
php vendor/bin/laminas cycle:migrator:run
```

#### Options
This command has no additional options.


### 2. `cycle:migrator:rollback` Command

#### Description
The `cycle:migrator:rollback` command undoes the changes made by the last migration, reverting your database schema to its previous state.

#### Usage
```bash
php vendor/bin/laminas cycle:migrator:rollback
```

#### Options
This command does not have any additional options.


### 3. `cycle:migrator:create` Command

#### Description
The `cycle:migrator:create` command generates a new empty migration file in the migration directory.

#### Usage
```bash
php vendor/bin/laminas cycle:migrator:create PascalCaseMigrationName
```

#### Options
- `migrationName`: The name of the migration file to be created. This should be in PascalCase format.

**Important Note**: Migration filenames follow the format `YYYYMMDD.HHMMSS_0_counter_MigrationName.php` where:
- `YYYYMMDD.HHMMSS` is the timestamp when the migration was created
- `0` is a fixed value
- `counter` is an incremental number (starting from 0) for migrations with the same name
- `MigrationName` is the name you provided in PascalCase

**Note**: Make sure that you have correctly configured the database connection and migrations settings in your project's configuration file.

For more information about using migrations with Cycle ORM, see the [Cycle ORM Documentation](https://cycle-orm.dev/docs/database-migrations/current/en).


### 4. `cycle:cache:clear` Command

#### Description

The `cycle:schema:cache:clear` command clears the cached Cycle ORM schema configuration, forcing the ORM to
regenerate it on the next application run. This can be useful during development when changes to entity definitions or
configurations have been made, and you want to ensure fresh schema generation.

#### Usage

```bash
php vendor/bin/laminas cycle:cache:clear
```

#### Options

This command does not have any additional options.

### 5. `cycle:seed:create` Command

#### Description

The `cycle:seed:create` command creates a new seed file in the seed directory. Seed files are used to populate your database with initial or test data.

#### Usage

```bash
php vendor/bin/laminas cycle:seed:create SeedName
```

#### Arguments

- `SeedName`: The name of the seed in PascalCase format (required).

**Note**: The generated seed file will be placed in the configured seed directory and will implement the `SeedInterface`.


### 6. `cycle:seed:run` Command

#### Description

The `cycle:seed:run` command executes seed files, populating your database with the data defined in the seeds. You can run a specific seed or all seeds in the configured directory.

#### Usage

```bash
# Run a specific seed
php vendor/bin/laminas cycle:seed:run SeedName

# Alternative ways to specify the seed
php vendor/bin/laminas cycle:seed:run --seed SeedName
php vendor/bin/laminas cycle:seed:run -s SeedName

# Run all seeds in the configured directory
php vendor/bin/laminas cycle:seed:run
```

#### Arguments and Options

- `SeedName`: The name of the seed to run in PascalCase format, without the .php extension.
- `--seed` or `-s`: Alternative ways to specify the seed name.

If no seed name is provided, the command will run all seeds in the configured seed directory.

**Note**: All seed classes must implement the `SeedInterface` and be located in the configured seed directory. The command will automatically inject the database connection into the seed class.


### Cache Configuration Example

You can create a Symfony Cache Filesystem Adapter for your application like this:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return [
    'dependencies' => [
        'factories' => [
            'Cache\Symfony\Filesystem' => function () {
                // Create a Symfony Cache File Adapter instance
                return new FilesystemAdapter(
                    'cycle', // Namespace prefix for cache keys
                    0, // Default TTL of items in seconds (0 means infinite)
                    'data/cycle/cache' // Absolute or relative directory for cache files storage
                );
            },
        ],
    ],
];
```

This configuration creates a Filesystem Cache Adapter with the following parameters:
- `'cycle'`: A namespace prefix for cache keys, helping to avoid key collisions
- `0`: Default Time-To-Live (TTL) set to infinite, meaning cached items won't expire automatically
- `'data/cycle/cache'`: Directory where cache files will be stored


The cache configuration will look like this:
```php
    'cache' => [
        'enabled' => true,
        'key' => 'cycle_orm_cached_schema',
        'service' => 'Cache\Symfony\Filesystem',
    ],
```
