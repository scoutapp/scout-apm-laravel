<?php

namespace Scoutapm\Laravel\Middleware;

use Scoutapm\Agent;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class SendRequestToScout
{
    protected $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {
            $this->agent->send();
            Log::debug("[Scout] SendRequestToScout succeeded");
        } catch (\Exception $e) {
            Log::debug("[Scout] SendRequestToScout failed: " . $e);
        }

        return $response;
    }

    public function terminate($request, $response)
    {
    }
}
