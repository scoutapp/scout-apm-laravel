<?php

namespace Scoutapm\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class ScoutApm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'scoutapm';
    }
}
