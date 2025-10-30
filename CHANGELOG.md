# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.6.0] - 30/10/2025

### Added
- Seed command can run seeds from another directory via `--directory` / `-d` option (in addition to the configured `seed_directory`).

### Changed
- Renamed migrator configuration key for seed directory from `seed-directory` to `seed_directory` (update your config accordingly).
- Updated internal tooling: added Rector configuration and PHPStan setup; normalized composer files for tools.
- Improved factories for migrator and seed commands to streamline dependency wiring and developer experience.
- Polished tests for migration and seed commands.
- Extended migrator configuration support: now recognizes `vendor_directories` and `namespace` (mapped to Cycle Migrations config keys), alongside existing options like `directory`, `table`, and `safe`.

### Fixed
- Migration class namespace is now correctly generated from migrator config (`namespace`).
- Minor fixes in `CreateMigrationCommand` and seed command factory to align with latest tooling.
- Documentation and metadata adjustments.

## [2.5.0] - 28/09/2025

### Changed
- Migration-related console commands are now registered only when the optional `cycle/migrations` package is installed. They no longer appear in CLI help if the package is absent.
- Non-migration commands (e.g., `cycle:cache:clear`) remain available regardless of the presence of the migrations package.
- Schema-migrations generator commands are now exposed only when the `cycle/schema-migrations-generator` package is installed and migrations are enabled.

### Added
- Optional feature flag to disable migration command registration even when the package is installed: set environment variable `CYCLE_MIGRATIONS_DISABLED` to a truthy value (`1`, `true`, `yes`, `on`). To explicitly keep migrations enabled, set it to a falsy value (`0`, `false`, `no`, `off`) or leave it unset.
- Unit tests covering command registration with migrations enabled and with migrations disabled by the feature flag.
- Internal helper to toggle migrations availability at runtime based on installed packages and the env flag.

## [2.4.1] - 23/09/2025

### Fixed
- Fixed typo in namespace for `SeedInterface` import

### Changed
- Removed deprecated `composer/package-versions-deprecated` dependency from `composer.json`

## [2.4.0] - 22/09/2025

### Changed
- Removed direct dependency on laminas-cli, moved it to suggest section
- Added symfony/console as a direct dependency for command implementation
- Updated README.md to show both symfony/console (direct) and laminas-cli (optional) command usage examples

## [2.3.1] - 30/08/2025

### Fixed
- Corrected PHP 8.1+ compatibility constraints and tooling versions in composer.json
- Fixed seed template in `CreateSeedCommand` to correctly reference `SeedInterface` namespace and remove stray backslash

### Changed
- Minor improvements to seed creation messaging and validation

## [2.3.0] - 25/05/2025

### Added
- Added interface aliases in ConfigProvider for easier service access:
  - `Cycle\Database\DatabaseInterface` => `'dbal'`
  - `Sirix\Cycle\Service\MigratorInterface` => `'migrator'`
  - `Cycle\ORM\ORMInterface` => `'orm'`

### Changed
- Internal refactoring of factory classes for improved maintainability
- Updated command factories for better dependency management
- Enhanced migration filename generation to automatically increment counter for duplicate migration names

## [2.2.0] - 23/05/2025

### Added
- Enhanced seed command functionality with support for running all seeds
- Added option to specify seed name using `-s` or `--seed` options
- Improved error handling and validation in seed commands

### Changed
- Updated documentation with more detailed examples for seed commands
- Improved test coverage for seed commands

## [2.1.1] - 17/05/2025

### Fixed
- Fixed typo in cache service configuration in README
- Removed version field from composer.json
- Refactored long assertion for improved readability
- Fixed missing GenerateSeed and RunSeed commands in `ConfigProvider::getCliConfig` method

## [2.1.0] - 16/05/2025

### Added
- Seed functionality with two new commands:
  - `cycle:seed:create` - Creates a new seed file in the configured seed directory
  - `cycle:seed:run` - Executes a specific seed file to populate the database with data
- New `SeedInterface` for implementing seed classes
- Configuration option for seed directory in the migrator configuration (`seed-directory`)
- File name validation for seed creation

### Changed
- Reorganized command structure by moving commands to appropriate namespaces
- Improved test organization to match the new command structure
- Changed command name from `cycle:migrator:generate` to `cycle:migrator:create`

### Fixed
- Various code improvements and bug fixes
