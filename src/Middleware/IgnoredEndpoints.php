<?php

namespace Scoutapm\Laravel\Middleware;

use Scoutapm\Agent;

use Closure;
use Illuminate\Support\Facades\Route;

class IgnoredEndpoints
{
    protected $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle($request, Closure $next)
    {
        // Check if the request path we're handling is configured to be
        // ignored, and if so, mark it as such.
        if ($this->agent->ignored($request->path()))
        {
            $this->$agent->getLogger()->info("Marking request to ".$request->path()." as ignored");
            $this->$agent->ignore();
        }

        return $next($request);
    }

    public function terminate($request, $response)
    {
    }
}
