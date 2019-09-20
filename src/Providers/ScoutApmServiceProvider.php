<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Engine;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Psr\Log\LoggerInterface;
use Scoutapm\Agent;
use Scoutapm\Config;
use Scoutapm\Laravel\Database\QueryListener;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use Scoutapm\Laravel\Middleware\IgnoredEndpoints;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Laravel\Middleware\SendRequestToScout;
use Scoutapm\Laravel\View\Engine\ScoutViewEngineDecorator;
use Scoutapm\ScoutApmAgent;

final class ScoutApmServiceProvider extends ServiceProvider
{
    private const SCOUTAPM_ALIAS_SERVICE_KEY = 'scoutapm';

    private const VIEW_ENGINES_TO_WRAP = ['file', 'php', 'blade'];

    /** @throws BindingResolutionException */
    public function register() : void
    {
        $this->app->singleton(ScoutApmAgent::class, static function (Application $app) {
            return Agent::fromConfig(
                new Config(),
                $app->make('log')
            );
        });

        $this->app->alias(ScoutApmAgent::class, Agent::class);
        $this->app->alias(ScoutApmAgent::class, self::SCOUTAPM_ALIAS_SERVICE_KEY);

        /** @var EngineResolver $viewResolver */
        $viewResolver = $this->app->make('view.engine.resolver');

        foreach (self::VIEW_ENGINES_TO_WRAP as $engineName) {
            $realEngine = $viewResolver->resolve($engineName);

            $viewResolver->register($engineName, function () use ($realEngine) {
                return $this->wrapEngine($realEngine);
            });
        }
    }

    /** @throws BindingResolutionException */
    public function wrapEngine(Engine $realEngine) : Engine
    {
        return new ScoutViewEngineDecorator(
            $realEngine,
            $this->app->make(ScoutApmAgent::class),
            $this->app->make('view')->getFinder()
        );
    }

    public function boot(Kernel $kernel, ScoutApmAgent $agent, LoggerInterface $log) : void
    {
        $agent->connect();

        $log->debug('[Scout] Agent is starting');

        $this->installInstruments($kernel, $agent);
    }

    /**
     * This installs all the instruments right here. If/when the laravel specific instruments grow, we should extract
     * them to a dedicated instrument manager as we add more.
     */
    public function installInstruments(Kernel $kernel, ScoutApmAgent $agent) : void
    {
        DB::listen(new QueryListener($agent));

        $kernel->prependMiddleware(MiddlewareInstrument::class);
        $kernel->pushMiddleware(ActionInstrument::class);

        // Must be outside any other scout instruments. When this middleware's terminate is called, it will complete
        // the request, and send it to the CoreAgent.
        $kernel->prependMiddleware(IgnoredEndpoints::class);
        $kernel->prependMiddleware(SendRequestToScout::class);
    }
}
