<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Scoutapm\Logger\FilteredLogLevelDecorator;
use Scoutapm\ScoutApmAgent;
use Throwable;

final class SendRequestToScout
{
    /** @var ScoutApmAgent */
    private $agent;

    /** @var FilteredLogLevelDecorator */
    private $logger;

    public function __construct(ScoutApmAgent $agent, FilteredLogLevelDecorator $logger)
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
            $this->logger->debug('SendRequestToScout succeeded');
        } catch (Throwable $e) {
            $this->logger->debug('SendRequestToScout failed: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $response;
    }
}
