<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Scoutapm\Agent;
use Throwable;

final class SendRequestToScout
{
    /** @var Agent */
    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function handle(Request $request, Closure $next) : Response
    {
        $response = $next($request);

        try {
            $this->agent->send();
            Log::debug('[Scout] SendRequestToScout succeeded');
        } catch (Throwable $e) {
            Log::debug('[Scout] SendRequestToScout failed: ' . $e);
        }

        return $response;
    }
}
