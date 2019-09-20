<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $this->agent = $agent;
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next) : Response
    {
        $response = $next($request);

        try {
            $this->agent->send();
            $this->logger->debug('[Scout] SendRequestToScout succeeded');
        } catch (Throwable $e) {
            $this->logger->debug('[Scout] SendRequestToScout failed: ' . $e);
        }

        return $response;
    }
}
