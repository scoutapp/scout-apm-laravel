<?php

namespace Scoutapm\Laravel\Events;

use Scoutapm\Agent;

use Illuminate\Support\Facades\Log;

class TemplateCreatedEvent {

    private $agent;

    /**
     * Create the event listener.
     *
     * @param Agent $agent
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        Log::info('installed templatecreatedevent');
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
        // $this->agent->startSpan('Template/Compile/' . $view->getName()); // Start when View is Creating
        Log::info('Template/Compile/' . microtime());
    }

}
