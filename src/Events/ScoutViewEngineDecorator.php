<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Events;

use Illuminate\Contracts\View\Engine;
use Scoutapm\Agent;

final class ScoutViewEngineDecorator implements Engine
{
    /** @var Engine */
    private $realEngine;

    /** @var Agent */
    private $agent;

    public function __construct(Engine $realEngine, Agent $agent)
    {
        $this->realEngine = $realEngine;
        $this->agent = $agent;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $this->agent->startSpan('viewEngine');

        $return = $this->realEngine->get($path, $data);

        $this->agent->stopSpan();

        return $return;
    }
}
