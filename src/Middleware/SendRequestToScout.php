<?php

namespace Scoutapm\Laravel\Middleware;

use Scoutapm\Agent;

use Closure;
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
            $this->agent->getLogger()->debug("[Scout] SendRequestToScout succeeded");
        } catch (\Exception $e) {
            $this->agent->getLogger()->debug("[Scout] SendRequestToScout failed: " . $e);
        }

        return $response;
    }

    public function terminate($request, $response)
    {
    }
}
