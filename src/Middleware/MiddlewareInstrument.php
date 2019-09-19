<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function handle(Request $request, Closure $next) : Response
    {
        Log::debug('[Scout] Handle MiddlewareInstrument');

        return $this->agent->instrument('Middleware', 'all', static function () use ($request, $next) {
            return $next($request);
        });
    }
}
