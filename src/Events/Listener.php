<?php

namespace Scoutapm\Laravel\Events;

use Illuminate\Routing\Events\RouteMatched;
use Scoutapm\Agent;

class Listener {

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
     * @param  object $event
     * @param  object $data
     * @return void
     */
    public function handle($event, $data)
    {
        $routeMatchedString = RouteMatched::class;
        if (substr($event, 0, strlen($routeMatchedString)) === $routeMatchedString) {
            $routeMatched = reset($data);
            $route = $routeMatched->route;

            $name = 'Controller/' . $route->uri;
            if (isset($route->action['controller'])) {
                $name = 'Controller/' . $route->action['controller'];
            }

            $this->agent->startSpan($name, LARAVEL_START);

        }

        $creatingString = 'creating: ';
        if (substr($event, 0, strlen($creatingString)) === $creatingString) {
            $view = reset($data);
            $this->agent->tagSpan('Template/Compile', $view->getName(), microtime(true));
        }

        $composingString = 'composing: ';
        if (substr($event, 0, strlen($composingString)) === $composingString) {
            $view = reset($data);
            $this->agent->tagSpan('Template/Render', $view->getName(), microtime(true));
        }
    }

}
