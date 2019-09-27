<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Scoutapm\ScoutApmAgent;
use Throwable;

final class SendRequestToScout
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
        $this->agent->connect();

        $response = $next($request);

        try {
            $this->agent->send();
            $this->logger->debug('[Scout] SendRequestToScout succeeded');
        } catch (Throwable $e) {
            $this->logger->debug('[Scout] SendRequestToScout failed: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $response;
    }
}
