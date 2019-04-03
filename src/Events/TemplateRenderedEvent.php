<?php

namespace Scoutapm\Laravel\Events;

use Scoutapm\Agent;

class TemplateRenderedEvent {

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
        $view = reset($data);
        $this->agent->startSpan('Template/Render/' . $view->getName()); // Start when View is Composing
    }

}
