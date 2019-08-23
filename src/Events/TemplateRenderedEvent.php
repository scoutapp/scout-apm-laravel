<?php

namespace Scoutapm\Laravel\Events;

use Scoutapm\Agent;

use Illuminate\Support\Facades\Log;

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
        Log::info('installed templaterenderedevent');
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
        // $this->agent->startSpan('Template/Render/' . $view->getName()); // Start when View is Composing
        Log::info('Template/Render/' . microtime());
    }

}
