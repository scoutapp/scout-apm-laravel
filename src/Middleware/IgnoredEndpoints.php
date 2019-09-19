<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Scoutapm\Agent;

final class IgnoredEndpoints
{
    /** @var Agent */
    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle(Request $request, Closure $next) : Response
    {
        // Check if the request path we're handling is configured to be
        // ignored, and if so, mark it as such.
        if ($this->agent->ignored('/' . $request->path())) {
            $this->agent->ignore();
        }

        return $next($request);
    }
}
