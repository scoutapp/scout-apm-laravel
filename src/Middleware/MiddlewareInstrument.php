<?php

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;

class MiddlewareInstrument
{
    protected $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle($request, Closure $next)
    {
        Log::debug("[Scout] Handle MiddlewareInstrument");
        $this->agent->startSpan("Middleware/all");

        $response = $next($request);

        $this->agent->stopSpan();

        return $response;
    }
}
