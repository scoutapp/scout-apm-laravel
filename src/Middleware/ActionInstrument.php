<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Exception;
use Scoutapm\Agent;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Scoutapm\Events\Span\Span;
use Throwable;

final class ActionInstrument
{
    /** @var Agent */
    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    /** @throws Throwable */
    public function handle($request, Closure $next)
    {
        Log::debug('[Scout] Handle ActionInstrument');

        return $this->agent->webTransaction(
            'unknown',
            function (Span $span) use ($request, $next) {
                $response = $next($request);

                $span->updateName($this->automaticallyDetermineControllerName());

                return $response;
            }
        );
    }

    /**
     * Get the name of the controller span from the controller name if possible, but fall back on the uri if no
     * controller was found.
     */
    private function automaticallyDetermineControllerName() : string
    {
        $name = 'unknown';

        try {
            $route = Route::current();
            if ($route !== null) {
                $name = $route->action['controller'] ?? $route->uri();
            }
        } catch (Exception $e) {
            Log::debug('[Scout] Exception obtaining name of endpoint: getName()', ['exception' => $e]);
        }

        return 'Controller/' . $name;
    }
}
