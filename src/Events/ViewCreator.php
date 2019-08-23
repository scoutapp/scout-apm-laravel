<?php

namespace Scoutapm\Laravel\Events;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;

class ViewCreator
{
    /**
     *
     * @var Agent
     */
    protected $agent;

    /**
     * Create a new view creator
     *
     * @param Agent $agent
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    /**
     *
     * @param  View  $view
     * @return void
     */
    public function create(View $view)
    {
        // $this->agent->tagSpan("Template/Compile", $view->getName(), microtime(true));
        // $this->agent->stopSpan(); // Stop after View is Created
        Log::info("ViewCreator: ".microtime());
    }
}
