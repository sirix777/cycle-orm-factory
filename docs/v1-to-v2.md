# Migration Guide: Cycle ORM Factory v1 to v2

This guide will help you migrate your application from version 1.x to version 2.x of the Cycle ORM Factory for Mezzio.

### Dependency Updates

With the migration to Cycle ORM Factory v2, the dependencies in your application may need updates to ensure
compatibility with the new version. Here are the changes related to dependencies:

#### PSR-6 Cache Implementation

Cycle ORM Factory v2 now requires a PSR-6 compliant cache implementation for schema caching. If your project does not
already include one, consider adding a library such as `symfony/cache`.

To install `symfony/cache` for filesystem-based caching:

```shell
composer require symfony/cache
```

#### Updated Package Versions

In addition to installing PSR-6 compatible cache packages, ensure that your application dependencies match the
requirements of Cycle ORM Factory v2. Specifically, update the Cycle ORM Factory package as follows:

```shell
composer require sirix/cycle-orm-factory:^2.0
```

#### Laminas CLI Integration

As part of the migration to Cycle ORM Factory v2, the `laminas/laminas-cli` package has been introduced as a dependency. This ensures better integration with Laminas-based applications and provides a consistent
development experience.


By updating dependencies, you ensure that your application is fully compatible with the new features and requirements
introduced in Cycle ORM Factory v2.

## Key Changes in v2

### Configuration Changes

In Cycle ORM Factory v2, the configurations for `schema`, `migrator`, and `entities` have been moved under the `cycle` key. Additionally, database configurations have been relocated under `cycle => [ 'db-config' => [`.

#### Example:

```php
'cycle' => [
    'db-config' => [
        // Database configurations here...
    ],
    'schema' => [
        // Schema configurations here...
    ],
    'migrator' => [
        // Migrator configurations here...
    ],
    'entities' => [
        // Entities configurations here...
    ],
],
```

### Cache Configuration

The cache configuration has been updated in v2 to provide better flexibility and integration with PSR-compliant cache implementations.

#### v1 Configuration (Old)

```php
'schema' => [
    'cache' => true, // Simple boolean flag
    // Other schema configurations...
],
```

#### v2 Configuration (New)

```php
'cycle' => [
    'schema' => [
        'cache' => [
            'enabled' => true,
            'key' => 'cycle_cached_schema', // optional parameter
            'service' => CacheItemPoolInterface::class, // optional parameter
        ],
        // Other schema configurations...
    ],
]
```

### Cache Service Integration

In v2, you need to configure a cache service that implements `Psr\Cache\CacheItemPoolInterface`. Example using Symfony's Filesystem Adapter:

```php
<?php

declare(strict_types=1);

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return [
    'dependencies' => [
        'factories' => [
            CacheItemPoolInterface::class => function () {
                return new FilesystemAdapter(
                    'cycle', // Namespace prefix for cache keys
                    0, // Default TTL of items in seconds (0 means infinite)
                    'data/cycle/cache' // Cache directory
                );
            },
        ],
    ],
];
```

### Disabling the Cache in v2

If you wish to disable the cache in Cycle ORM Factory v2, you can set the `enabled` parameter in the `cache`
configuration to `false`. This approach completely disables schema caching.

#### Example Configuration

```php
'cycle' => [
    'schema' => [
        'cache' => [
            'enabled' => false,
        ],
        // Other schema configurations...
    ],
]
```

### Important Notes

- When caching is disabled, the ORM will rebuild the schema on every application execution, which may lead to
  performance overhead.
- This configuration is ideal for development environments where it is necessary to frequently update the schema or
  debug schema-related issues.
- In production environments, enabling caching is recommended for optimal performance.

## Migration Steps

1. **Update Package Version**

   Update the package requirement in your `composer.json`:

```shell script
composer require sirix/cycle-orm-factory:^2.0
```

2. **Change Configuration Key Structure**

   Update the configuration structure to match the new key hierarchy introduced in v2. Specifically, move database,
   schema, migrator, and entity configurations under the `cycle` key for better organization and clarity.

3. **Update Schema Cache Configuration**

   Replace your simple boolean cache configuration with the new structured format.

4. **Implement a Cache Service**

   Create a service factory for a PSR-6 compliant cache implementation.


## Breaking Changes

- Configuration keys such as `schema`, `migrator`, and `entities` are now grouped under the `cycle` key, and all
  database configurations have been moved under `cycle => [ 'db-config' => [ ... ]]`. Ensure you update your
  configuration files to adhere to the new structure.
- The `cache` configuration now requires a structured array instead of a simple boolean
- Direct cache integration now requires a PSR-6 compliant cache service

## Additional Notes

- If you don't specify a custom `service` in the cache configuration, the factory will look for a service named `cache` that implements `CacheItemPoolInterface`
- The default cache key has changed to `cycle_cached_schema` but can be customized

