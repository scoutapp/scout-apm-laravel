<?php

namespace Scoutapm\Laravel\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Scoutapm\Agent;

class ScoutApmServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/scoutapm.php' => config_path('scoutapm.php'),
        ], 'config');

        $agent = resolve(Agent::class);
        $request = $agent->startRequest('Request', LARAVEL_START);

        if (!app()->runningInConsole()) {
            DB::listen(function (QueryExecuted $query) use ($agent, $request) {
                $startingTime = microtime(true) - ($query->time / 1000);
                $requestSpan = $agent->getRequest('Request')->getFirstSpan();
                $span = $agent->startSpan('SQL/Query', 'Request', $startingTime, $requestSpan->getName());
                $agent->tagSpan('Request', 'db.statement', $query->sql, $request->getId(), $span->getId(), $startingTime+0.000001);
                $agent->stopSpan('SQL/Query', 'Request');
            });
        }

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
