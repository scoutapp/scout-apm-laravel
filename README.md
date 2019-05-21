# Scout Laravel APM Agent

Monitor the performance of PHP Laravel applications with Scout's PHP APM Agent.
Detailed performance metrics and transaction traces are collected once the scout-apm package is installed and configured.

## Requirements
* PHP Version: PHP 7.0+
* Laravel Version: 5.5+

## Quick Start
A Scout account is required. [Signup for Scout](https://apm.scoutapp.com/users/sign_up).

    composer require scoutapp/scoutapm-laravel
    
### API Key

In your `.env` file, make sure you set your Scout API key:

    SCOUT_KEY=ABC0ZABCDEFGHIJKLMNOP
    
### Middleware

You must enable our custom middleware in your `app/Http/Kernel.php`:

    \Scoutapm\Laravel\Middleware\LogRequest::class
    
## Documentation

For full installation and troubleshooting documentation, visit our
[help site](http://help.apm.scoutapp.com/#laravel-agent).


## Support

Please contact us at support@scoutapp.com or create an issue in this repo.
