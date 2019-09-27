<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $this->agent  = $agent;
        $this->logger = $logger;
    }

    /** @return mixed */
    public function handle(Request $request, Closure $next)
    {
        $this->logger->debug('[Scout] Handle MiddlewareInstrument');

        return $this->agent->instrument(
            'Middleware',
            'all',
            /** @return mixed */
            static function () use ($request, $next) {
                return $next($request);
            }
        );
    }
}
