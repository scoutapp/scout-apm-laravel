<?php

namespace Scoutapm\Laravel\Middleware;

use Scoutapm\Agent;

use Closure;
use Illuminate\Support\Facades\Route;

class ActionInstrument
{
    protected $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->agent->getLogger()->info("Installing ActionInstrument");
    }

    public function handle($request, Closure $next)
    {
        $this->agent->getLogger()->info("Handle ActionInstrument");
        $span = $this->agent->startSpan("Controller/unknown");

        $response = $next($request);

        $span->updateName($this->getName());

        $this->agent->stopSpan();

        return $response;
    }

    // Get the name of the controller span from the controller name if
    // possible, but fall back on the uri if no controller was found.
    public function getName() {
        try {
            $route = Route::current();
            if ($route == null) {
                return 'Controller/unknown';
            }

            $name = $route->uri();
            if (isset($route->action['controller'])) {
                $name =  $route->action['controller'];
            }
            return 'Controller/' . $name;
        } catch (\Exception $e) { 
            return 'Controller/unknown';
        }
    }
}
