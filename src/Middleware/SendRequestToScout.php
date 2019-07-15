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
        return $response;
    }

    public function terminate($request, $response)
    {
        try {
            $this->agent->send();
        } catch (\Exception $e) {
            $this->agent->getLogger()->info("SendRequestToScout failed: ");
        }
    }
}
