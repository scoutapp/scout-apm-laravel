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
    }

    public function handle($request, Closure $next)
    {
        $this->agent->getLogger()->debug("[Scout] Handle ActionInstrument");

        $span = $this->agent->startSpan("Controller/unknown");

        $response = $next($request);

        $span->updateName($this->getName());
        $this->agent->stopSpan();

        return $response;
    }

    // Get the name of the controller span from the controller name if
    // possible, but fall back on the uri if no controller was found.
    public function getName() {
        $name = 'unknown';
        try {
            $route = Route::current();
            if ($route != null) {
                $name = $route->uri();
                if (isset($route->action['controller'])) {
                    $name =  $route->action['controller'];
                }
            }
        } catch (\Exception $e) { 
            $this->agent->getLogger()->debug("[Scout] Exception obtaining name of endpoint: getName()");
        }

        return 'Controller/'.$name;
    }
}
