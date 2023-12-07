# Scout Laravel APM Agent

Monitor the performance of PHP Laravel applications with Scout's PHP APM Agent.
Detailed performance metrics and transaction traces are collected once the scout-apm package is installed and configured.

## Requirements

* PHP Version: PHP 7.2+ (PHP 8.3+ recommended)
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

#### Logging Verbosity

Once you have set up Scout and are happy everything is working, you can reduce the verbosity of the library's logging
system. The library is intentionally *very* noisy by default, which gives us more information to support our customers
if something is broken. However, if everything is working as expected, these logs can be reduced by setting the
`log_level` configuration key to a higher `Psr\Log\LogLevel`. For example, if you are using `.env` configuration:

```
SCOUT_LOG_LEVEL=error
```

Or if you are using `config/scout_apm.php`:

```php
$config[\Scoutapm\Config\ConfigKey::LOG_LEVEL] = \Psr\Log\LogLevel::ERROR;
```

Any of the constants defined in `\Psr\Log\LogLevel` are acceptable values for this configuration option.

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
