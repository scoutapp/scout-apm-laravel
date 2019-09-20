<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Scoutapm\ScoutApmAgent;

final class MiddlewareInstrument
{
    /** @var ScoutApmAgent */
    private $agent;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ScoutApmAgent $agent, LoggerInterface $logger)
    {
        $this->agent = $agent;
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next) : Response
    {
        $this->logger->debug('[Scout] Handle MiddlewareInstrument');

        return $this->agent->instrument(
            'Middleware',
            'all',
            static function () use ($request, $next) : Response {
                return $next($request);
            }
        );
    }
}
