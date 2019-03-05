<?php

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;

class LogRequest
{
    protected $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle($request, Closure $next)
    {
        $routeController = str_replace($request->route()->action['namespace'].'\\', '', $request->route()->action['controller']);
        $controllerName = 'Controller/'.$routeController;
        $this->agent->startSpan($controllerName, 'Request', LARAVEL_START+0.000001);

        $response = $next($request);

        $this->agent->stopSpan($controllerName, 'Request');
        $this->agent->stopRequest('Request');

        return $response;
    }

    public function terminate($request, $response)
    {
        try {
            $this->agent->send();
        }
        catch(\Throwable $t) {
            Log::error($t);
        }
    }
}
