<?php

return [
    'active'             => env('SCOUT_ACTIVE', true),
    'appName'            => env('SCOUT_APPNAME', 'Laravel'),
    'socketLocation'     => env('SCOUT_SOCKETLOCATION', '/tmp/core-agent.sock'),
    'key'                => env('SCOUT_KEY', null),
];
