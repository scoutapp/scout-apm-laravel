<?php

namespace Scoutapm\Laravel\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

use Scoutapm\Agent;
use Psr\Log\LoggerInterface;

use Scoutapm\Laravel\Events\ViewComposer;
use Scoutapm\Laravel\Events\ViewCreator;
use Scoutapm\Laravel\Providers\EventServiceProvider;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Laravel\Middleware\SendRequestToScout;



class ScoutApmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/scoutapm.php',
            'scoutapm'
        );

        $this->app->register(EventServiceProvider::class);

        $this->app->singleton(Agent::class, function ($app) {
            return new Agent();
        });

        $this->app->alias(Agent::class, 'scoutapm');
    }

    public function boot(Kernel $kernel, Agent $agent, LoggerInterface $log)
    {
        $agent->setLogger($log);

        // Allow the user to copy a default configuration file over to their
        // Laravel app's config directory. See-also our configuration
        // documentation for other approaches to Scout Agent configuration.
        $this->publishes([
            __DIR__ . '/../../config/scoutapm.php' => config_path('scoutapm.php'),
        ], 'config');

        //////////////
        // This installs all the instruments right here. If/when the laravel
        // specific instruments grow, we should extract them to other more
        // reasonable places.

        View::composer('*', ViewComposer::class);
        View::creator('*', ViewCreator::class);

        DB::listen(function (QueryExecuted $query) use ($agent) {
            $startingTime = microtime(true) - ($query->time / 1000);
            $span = $agent->startSpan('SQL/Query', $startingTime);
            $span->tag('db.statement', $query->sql);
            $agent->stopSpan();

            // TODO: figure out how to capture code location in a generic way (also specifically for db calls)
            // From Laravel DebugBar - https://github.com/barryvdh/laravel-debugbar/blob/master/src/DataCollector/QueryCollector.php#L193
        });

        $kernel->prependMiddleware(MiddlewareInstrument::class);
        $kernel->pushMiddleware(ActionInstrument::class);

        // Must be outside any other scout
        // instruments. When this middleware's
        // terminate is called, it will complete the
        // request, and send it to the CoreAgent.
        $kernel->prependMiddleware(SendRequestToScout::class);
    }
}
