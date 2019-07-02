<?php

namespace Scoutapm\Laravel\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;
use Scoutapm\Laravel\Events\RouteMatchedEvent;
use Scoutapm\Laravel\Events\TemplateCreatedEvent;
use Scoutapm\Laravel\Events\TemplateRenderedEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // RouteMatched::class => [RouteMatchedEvent::class],
        'creating: *' => [TemplateCreatedEvent::class],
        'composing: *' => [TemplateRenderedEvent::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
