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
        $this->agent->getLogger()->info("SendRequestToScout initialized");
    }

    public function handle($request, Closure $next)
    {
        $this->agent->getLogger()->info("SendRequestToScout handle");
        $response = $next($request);
        $this->agent->getLogger()->info("SendRequestToScout After handle");
        return $response;
    }

    public function terminate($request, $response)
    {
        try {
            $this->agent->getLogger()->info("SendRequestToScout terminate");
            $this->agent->send();
        } catch (\Exception $e) {
            $this->agent->getLogger()->info("SendRequestToScout failed: ");
        }
    }
}
