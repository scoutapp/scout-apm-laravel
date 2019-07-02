<?php

namespace Scoutapm\Laravel\Events;

use Illuminate\Routing\Events\RouteMatched;
use Scoutapm\Agent;

class RouteMatchedEvent {

    private $agent;

    /**
     * Create the event listener.
     *
     * @param Agent $agent
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Handle the event.
     *
     * @param RouteMatched $routeMatched
     * @return void
     */
    public function handle(RouteMatched $routeMatched)
    {
        $route = $routeMatched->route;

        $name = 'Controller/' . $route->uri;
        if (isset($route->action['controller'])) {
            $name = 'Controller/' . $route->action['controller'];
        }

        $this->agent->startSpan($name, LARAVEL_START);
    }
}
