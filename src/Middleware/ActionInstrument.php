<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Psr\Log\LoggerInterface;
use Scoutapm\Events\Span\Span;
use Scoutapm\ScoutApmAgent;
use Throwable;

final class ActionInstrument
{
    /** @var ScoutApmAgent */
    private $agent;

    /** @var LoggerInterface */
    private $logger;

    /** @var Router */
    private $router;

    public function __construct(ScoutApmAgent $agent, LoggerInterface $logger, Router $router)
    {
        $this->agent  = $agent;
        $this->logger = $logger;
        $this->router = $router;
    }

    /** @throws Throwable */
    public function handle(Request $request, Closure $next) : Response
    {
        $this->logger->debug('[Scout] Handle ActionInstrument');

        return $this->agent->webTransaction(
            'unknown',
            function (Span $span) use ($request, $next) : Response {
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
            $route = $this->router->current();
            if ($route !== null) {
                $name = $route->action['controller'] ?? $route->uri();
            }
        } catch (Throwable $e) {
            $this->logger->debug(
                '[Scout] Exception obtaining name of endpoint: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return 'Controller/' . $name;
    }
}
