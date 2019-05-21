<?php

return [
    'monitor'         => env('SCOUT_MONITOR', true),
    'name'            => env('SCOUT_NAME', 'Laravel'),
    'key'             => env('SCOUT_KEY', null),
    'socketLocation'  => env('SCOUT_SOCKETLOCATION', '/tmp/core-agent.sock'),
];
