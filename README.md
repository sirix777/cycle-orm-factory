## Cycle ORM factories for Mezzio

![GitHub stars](https://img.shields.io/github/stars/sirix777/cycle-orm-factory?style=flat-square)
![GitHub issues](https://img.shields.io/github/issues/sirix777/cycle-orm-factory?style=flat-square)
![GitHub license](https://img.shields.io/github/license/sirix777/cycle-orm-factory?style=flat-square)

Factories for use Cycle Orm in Mezzio Framework

### Install
```bash
composer require sirix/cycle-orm-factory
```

### Config 
config file example: config/autoload/cycle-orm.global.php
```php
<?php

declare(strict_types=1);

use Sirix\Cycle\Factory\CycleFactory;
use Sirix\Cycle\Factory\DbalFactory;
use Sirix\Cycle\Factory\MigratorFactory;
use Cycle\Database\Config;


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
        'property' => null,
        'cache'    => true,
        'directory' => 'data/cache'
    ],
];
```

### Use in your project
```php
$container->get('orm'); // Cycle\ORM\ORM
$container->get('dbal'); // Cycle\Database\DatabaseInterface
$container->get('migrator'); // Cycle\Migrations\Migrator
```