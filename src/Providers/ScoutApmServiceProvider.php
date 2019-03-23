<?php

namespace Scoutapm\Laravel\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Scoutapm\Agent;
use Scoutapm\Laravel\Events\ViewComposer;
use Scoutapm\Laravel\Events\ViewCreator;

class ScoutApmServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', ViewComposer::class);
        View::creator('*', ViewCreator::class);

        $this->publishes([
            __DIR__ . '/../../config/scoutapm.php' => config_path('scoutapm.php'),
        ], 'config');

        $agent = resolve(Agent::class);

        DB::listen(function (QueryExecuted $query) use ($agent) {
            $startingTime = microtime(true) - ($query->time / 1000);
            $agent->startSpan('SQL/Query', $startingTime);
            $agent->tagSpan('db.statement', $query->sql, $startingTime+0.000001);
            $agent->stopSpan();
        });

        // Automatically register middleware
        // $router = $this->app['router'];
        // $router->pushMiddlewareToGroup('web', ScoutApm\Middleware\LogRequest::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/scoutapm.php',
            'scoutapm'
        );

        $this->app->register(EventServiceProvider::class);

        $this->app->singleton(Agent::class, function ($app) {
            return new Agent(
                [
                    'active' => config('scoutapm.active'),
                    'appName' => config('scoutapm.appName'),
                    'socketLocation' => config('scoutapm.socketLocation'),
                    'key' => config('scoutapm.key'),
                ]
            );
        });
        $this->app->alias(Agent::class, 'scoutapm');
    }
}
