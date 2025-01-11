# Mezzio Cycle ORM Factory

![GitHub license](https://img.shields.io/github/license/sirix777/cycle-orm-factory?style=flat-square)

This package provides factories for integrating Cycle ORM into the Mezzio framework, providing seamless setup and configuration.
### Installation
```bash
composer require sirix/cycle-orm-factory
```

### Configuration
Create a configuration file, for example, `config/autoload/cycle-orm.global.php`:
```php
<?php

declare(strict_types=1);

use Cycle\Database\Config;
use Sirix\Cycle\Enum\SchemaProperty;


return [
    'cycle'           => [
        'default'     => 'default',
        'databases'   => [
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
    'migrator'        => [
        'directory' => 'db/migrations',
        'table'     => 'migrations'
    ],
    'entities'        => [
        'src/App/src/Entity',
    ],
    'schema'   => [
        'property' => SchemaProperty::GenerateMigrations,
        'cache'    => true,
        'directory' => 'data/cache'
    ],
];
```

### Migrator Configuration
```php
'migrator' => [
    'directory' => 'db/migrations',
    'table'     => 'migrations',
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

### Schema Configuration
```php
'schema' => [
    'property' => null,
    'cache'    => true,
    'directory' => 'data/cache'
],
```
- `property`: Configures the schema property, options include `null`, `SchemaProperty::SyncTables`, or `SchemaProperty::GenerateMigrations`.
- `cache`: Enables or disables caching of the generated schema.
- `directory`: Specifies the directory in which to store the cached schema.

### Schema Property Options

`SchemaProperty::SyncTables`

The SyncTables option synchronizes the database tables based on the provided entity classes. It ensures that the tables match the structure defined in the entity classes. This can be useful during development when the database schema evolves along with your application.

`SchemaProperty::GenerateMigrations`

The GenerateMigrations option is used to automatically generate migration files based on changes detected in your entity classes. When enabled, the ORM analyzes the differences between the current database schema and the defined entities. It then generates migration files to apply these changes, making it easier to version and manage your database schema changes.

Select the appropriate SchemaProperty option based on the requirements of your project. Customize the configuration to your needs and enjoy the seamless integration of Cycle ORM with Mezzio.

### Use in your project
```php
$container->get('orm'); // Cycle\ORM\ORM
$container->get('dbal'); // Cycle\Database\DatabaseInterface
$container->get('migrator'); // Cycle\Migrations\Migrator
```

These factories provide the necessary components to work seamlessly with Cycle ORM within the Mezzio Framework. Customize the configuration to meet the needs of your project.

For more information about Cycle ORM, see the [Cycle ORM documentation](https://cycle-orm.dev/docs).

## Migrator Commands
The `Sirix\Cycle\Command\Migrator` namespace provides two commands for managing database migrations using Cycle ORM. These commands are intended for use with the `laminas-cli` tool.

### 1. `migrator:migrate` Command

#### Description
The `migrator:migrate` command performs the necessary database migration steps, applying changes specified in migration files to synchronize your database schema.

#### Usage
```bash
php vendor/bin/laminas migrator:migrate
```

#### Options
This command has no additional options.


### 2. `migrator:rollback` Command

#### Description
The `migrator:rollback` command undoes the changes made by the last migration, reverting your database schema to its previous state.

#### Usage
```bash
php vendor/bin/laminas migrator:rollback
```

#### Options
This command does not have any additional options.


### 3. `migrator:create-migration` Command

#### Description
The `migrator:create-migration` command generates a new empty migration file in the migration directory.

#### Usage
```bash
php vendor/bin/laminas migrator:create-migration PascalCaseMigrationName
```

#### Options
- `migrationName`: The name of the migration file to be created. This should be in PascalCase format.

**Note**: Make sure that you have correctly configured the database connection and migrations settings in your project's configuration file.

For more information about using migrations with Cycle ORM, see the [Cycle ORM Documentation](https://cycle-orm.dev/docs/database-migrations/current/en).
