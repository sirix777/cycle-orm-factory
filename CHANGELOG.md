# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
