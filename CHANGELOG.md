# Changelog

## Pending [1.2.1]

### Changed

 - Configure Laravel framework name and version automatically (#42)

## [1.2.0] 2019-12-19

### Added

 - Added support for Laravel Queues (#36, #40)
 - Uncaught exceptions now cause the `error` tag to be added to the request (#39)

### Changed

 - [BC] Renamed Scout APM's config file to `scout_apm.php` - if you use this, you will need to rename yours too (#38)
   - This was done to avoid a conflict with [Laravel Scout](https://laravel.com/docs/6.x/scout) configuration.

## [1.1.0] 2019-12-09

 - Support for PHP 7.4 (#32)
 - Use Laravel's Config Repository for Scout configuration, so that config can now be cached (#34)

## [1.0.0] 2019-12-04

### Changed

 - Required `scoutapp/scout-apm-php` at `^2.0`
 - Use Laravel's Cache, if configured, for the agent to cache metadata
 - Removed duplicate `[Scout]` text from log messages

## [0.2.3] 2019-10-07

### Fixed

 - Type mismatch in JsonResponse Middlewares (#21)

## [0.2.2] 2019-09-26

### Fixed

 - CoreAgent now only connects during web requests, not other situations
 - Updates to underlying scout-apm-php v0.2.2

## [0.2.1] 2019-09-25

### Added

 - Lots of changes to make the agent more ergonomic
 - Updates to underlying scout-apm-php v0.2.1

## [0.2.0] 2019-08-23

### Added

 - View instrumentation for Blade, PHP, and File engines (#4)
 - Licensed as MIT (#6)

## [0.1.1] 2019-08-23

Initial Release. See documentation at https://docs.scoutapm.com

