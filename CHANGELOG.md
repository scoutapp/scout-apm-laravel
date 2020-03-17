# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.0 - 2020-03-17

### Added

- [#51](https://github.com/scoutapp/scout-apm-laravel/pull/51) Added support for Laravel 7.x series

### Changed

- [#52](https://github.com/scoutapp/scout-apm-laravel/pull/52) Require minimum ^4.2 for scout-apm-php to upgrade to core-agent 1.2.8

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.3 - 2020-01-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#46](https://github.com/scoutapp/scout-apm-laravel/pull/46) Fixed bug with interaction with blade directives from third party plugins

## 1.2.2 - 2020-01-09

### Added

- [#44](https://github.com/scoutapp/scout-apm-laravel/pull/44) Added configuration to disable Laravel Queues instrumentation, `disabled_instruments=["laravel_queues"]`

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2019-12-31

### Added

- [#42](https://github.com/scoutapp/scout-apm-laravel/pull/42) Configure Laravel framework name and version automatically

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2019-12-19

### Added

- [#36](https://github.com/scoutapp/scout-apm-laravel/pull/36), [#40](https://github.com/scoutapp/scout-apm-laravel/pull/40) Added support for Laravel Queues
- [#39](https://github.com/scoutapp/scout-apm-laravel/pull/39) Uncaught exceptions now cause the `error` tag to be added to the request

### Changed

- [#38](https://github.com/scoutapp/scout-apm-laravel/pull/38) **[BC]** Renamed Scout APM's config file to `scout_apm.php` - if you use this, you will need to rename yours too (#38)
  - This was done to avoid a conflict with [Laravel Scout](https://laravel.com/docs/6.x/scout) configuration.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.0 - 2019-12-09

### Added

- Nothing.

### Changed

- [#32](https://github.com/scoutapp/scout-apm-laravel/pull/32) Support for PHP 7.4
- [#34](https://github.com/scoutapp/scout-apm-laravel/pull/34) Use Laravel's Config Repository for Scout configuration, so that config can now be cached

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.0 - 2019-12-04

### Added

- Nothing.

### Changed

- Required `scoutapp/scout-apm-php` at `^2.0`
- Use Laravel's Cache, if configured, for the agent to cache metadata
- Removed duplicate `[Scout]` text from log messages

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.3 - 2019-10-07

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#21](https://github.com/scoutapp/scout-apm-laravel/pull/21) Type mismatch in JsonResponse Middlewares

## 0.2.2 - 2019-09-26

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- CoreAgent now only connects during web requests, not other situations
- Updates to underlying scout-apm-php v0.2.2

## 0.2.1 - 2019-09-25

### Added

- Lots of changes to make the agent more ergonomic
- Updates to underlying scout-apm-php v0.2.1

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.0 - 2019-08-23

### Added

- [#4](https://github.com/scoutapp/scout-apm-laravel/pull/4) View instrumentation for Blade, PHP, and File engines
- [#6](https://github.com/scoutapp/scout-apm-laravel/pull/6) Licensed as MIT

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.1 - 2019-08-23

### Added

- Initial Release. See documentation at https://docs.scoutapm.com

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
