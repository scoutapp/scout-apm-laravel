# Scout Laravel APM Agent

Monitor the performance of PHP Laravel applications with Scout's PHP APM Agent.
Detailed performance metrics and transaction traces are collected once the scout-apm package is installed and configured.

## Requirements
* PHP Version: PHP 7.0+
* Laravel Version: 5.5+

## Quick Start
A Scout account is required. [Signup for Scout](https://apm.scoutapp.com/users/sign_up).

    composer require scoutapp/scoutapm-laravel
    
### Configuration

In your `.env` file, make sure you set a few configuration variables:

    SCOUT_KEY=ABC0ZABCDEFGHIJKLMNOP
    SCOUT_NAME="My Laravel App"
    SCOUT_MONITOR=true
    
Your key can be found in the Scout Org settings page.
    
## Documentation

For full installation and troubleshooting documentation, visit our
[help site](http://help.apm.scoutapp.com/#laravel-agent).


## Support

Please contact us at support@scoutapp.com or create an issue in this repo.

## Custom Instrumentation

```
$request = new ServiceRequest();
$request->setApiVersion($version);
```

Turns into:

```
// At top, with other imports
use ScoutApm;

// Replacing the above code
$request = ScoutApm::instrument(
    "Custom", "Building Service Request",
    function ($span) use ($version) {
        $request = new ServiceRequest();
        $request->setApiVersion($version);
        return $request;
    }
);
```

