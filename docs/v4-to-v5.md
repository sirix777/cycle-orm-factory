# Migration Guide: Cycle ORM Factory v4 to v5

Version 5 fixes the container binding for `Cycle\Database\DatabaseInterface`. Check any application
code that resolves this service ID before upgrading.

## Service changes

| Service ID | v4 result | v5 result |
|---|---|---|
| `dbal` | `DatabaseManager` | `DatabaseManager` |
| `DatabaseInterface::class` | `DatabaseManager` through the `dbal` alias | Default `DatabaseInterface` from the same manager |
| `DatabaseProviderInterface::class` | No package binding | Alias of `dbal` |

If code retrieves `DatabaseInterface::class` and calls `database($name)` on the result, request
`DatabaseProviderInterface::class` instead. Code that needs the manager specifically can continue
to request `dbal`.

```php
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;

// Choose a configured database by name.
$provider = $container->get(DatabaseProviderInterface::class);
$analytics = $provider->database('analytics');

// Use the default database directly.
$default = $container->get(DatabaseInterface::class);
$default->transaction(static function (DatabaseInterface $db): void {
    // atomic work
});
```

For the default database, `get(DatabaseInterface::class)` and
`get('dbal')->database()` return the same object when the container shares `dbal`. Keep `dbal`
shared so the ORM and database services use the same manager.

## Optional named database services

Applications using Laminas ServiceManager can expose configured databases as container services
through `cycle.database_services`. Install `laminas/laminas-servicemanager` if the application does
not already provide it, then map each desired service ID to a Cycle database name:

```php
return [
    'cycle' => [
        'database_services' => [
            'db.analytics' => 'analytics',
        ],
    ],
];
```

The database name must also exist in `cycle.db-config.databases` with a configured connection.
See the complete example in the [README](../README.md#named-database-services-with-laminas-servicemanager).
Other PSR-11 containers can register named databases with their own factories.

## Transaction lifetime

The binding fix does not change connection lifetime. A long-running worker that shares `dbal`
between requests may also retain its databases and drivers. Close manual transactions on every
path, or use `DatabaseInterface::transaction()` for an operation that commits on success and rolls
back on exception. The application or runner owns any cleanup at the request boundary.
