<?php

return [
    'active'             => env('SCOUTAPM_ACTIVE', true),
    'appName'            => env('SCOUTAPM_APPNAME', 'Laravel'),
    'socketLocation'     => env('SCOUTAPM_SOCKETLOCATION', '/tmp/core-agent.sock'),
    'key'                => env('SCOUTAPM_KEY', null),
];
