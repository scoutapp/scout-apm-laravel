<?php

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;

final class MiddlewareInstrument
{
    /** @var Agent */
    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle($request, Closure $next)
    {
        Log::debug('[Scout] Handle MiddlewareInstrument');

        return $this->agent->instrument('Middleware', 'all', static function () use ($request, $next) {
            return $next($request);
        });
    }
}
