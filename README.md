# Scout Laravel APM Agent

[![Build](https://github.com/scoutapp/scout-apm-laravel/workflows/Build/badge.svg?branch=master&event=push)](https://github.com/scoutapp/scout-apm-laravel/actions?query=branch%3Amaster) [![Latest Stable Version](https://poser.pugx.org/scoutapp/scout-apm-laravel/v/stable)](https://packagist.org/packages/scoutapp/scout-apm-laravel) [![Total Downloads](https://poser.pugx.org/scoutapp/scout-apm-laravel/downloads)](https://packagist.org/packages/scoutapp/scout-apm-laravel) [![License](https://poser.pugx.org/scoutapp/scout-apm-laravel/license)](https://packagist.org/packages/scoutapp/scout-apm-laravel)

Monitor the performance of PHP Laravel applications with Scout's PHP APM Agent.
Detailed performance metrics and transaction traces are collected once the scout-apm package is installed and configured.

## Requirements

* PHP Version: PHP 7.1+
* Laravel Version: 5.5+

## Quick Start

A Scout account is required. [Signup for Scout](https://scoutapm.com/users/sign_up).

```bash
composer require scoutapp/scout-apm-laravel
```

Then use Laravel's `artisan vendor:publish` to ensure configuration can be cached:

```bash
php artisan vendor:publish --provider="Scoutapm\Laravel\Providers\ScoutApmServiceProvider"
```

### Configuration

In your `.env` file, make sure you set a few configuration variables:

```
SCOUT_KEY=ABC0ZABCDEFGHIJKLMNOP
SCOUT_NAME="My Laravel App"
SCOUT_MONITOR=true
```
    
Your key can be found in the [Scout organization settings page](https://scoutapm.com/settings).
    
## Documentation

For full installation and troubleshooting documentation, visit our [help site](https://docs.scoutapm.com/#laravel).

## Support

Please contact us at support@scoutapm.com or create an issue in this repo.

## Capabilities

The Laravel library:

 * Registers a service `\Scoutapm\ScoutApmAgent::class` into the container (useful for dependency injection)
 * Provides a Facade `\Scoutapm\Laravel\Facades\ScoutApm`
 * Wraps view engines to monitor view rendering times
 * Injects several middleware for monitoring controllers and sending statistics to the Scout Core Agent
 * Adds a listener to the database connection to instrument SQL queries

## Custom Instrumentation

In order to perform custom instrumentation, you can wrap your code in a call to the `instrument` method. For example,
given some code to be monitored:

```php
$request = new ServiceRequest();
$request->setApiVersion($version);
```

Using the provided Facade for Laravel, you can wrap the call and it will be monitored.

```php
// At top, with other imports
use Scoutapm\Events\Span\Span;
use Scoutapm\Laravel\Facades\ScoutApm;

// Replacing the above code
$request = ScoutApm::instrument(
    'Custom',
    'Building Service Request',
    static function (Span $span) use ($version) {
        $request = new ServiceRequest();
        $request->setApiVersion($version);
        return $request;
    }
);
```
