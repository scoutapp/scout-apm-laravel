<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Scoutapm\ScoutApmAgent;

final class IgnoredEndpoints
{
    /** @var ScoutApmAgent */
    private $agent;

    public function __construct(ScoutApmAgent $agent)
    {
        $this->agent = $agent;
    }

    /** @return mixed */
    public function handle(Request $request, Closure $next)
    {
        // Check if the request path we're handling is configured to be
        // ignored, and if so, mark it as such.
        if ($this->agent->ignored('/' . $request->path())) {
            $this->agent->ignore();
        }

        return $next($request);
    }
}
