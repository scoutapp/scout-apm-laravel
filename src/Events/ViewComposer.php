<?php

namespace Scoutapm\Laravel\Events;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;

class ViewComposer
{
    /**
     *
     * @var Agent
     */
    protected $agent;

    /**
     * Create a new view composer
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
    public function compose(View $view)
    {
        // $this->agent->tagSpan("Template/Render", $view->getName(), microtime(true));
        // $this->agent->stopSpan(); // Stop after View is Composed
        Log::info("ViewComposer: ".microtime());
    }
}
