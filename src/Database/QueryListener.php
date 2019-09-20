<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Database;

use Illuminate\Database\Events\QueryExecuted;
use Scoutapm\ScoutApmAgent;
use function microtime;

final class QueryListener
{
    /** @var ScoutApmAgent */
    private $agent;

    public function __construct(ScoutApmAgent $agent)
    {
        $this->agent = $agent;
    }

    public function __invoke(QueryExecuted $query) : void
    {
        $startingTime = microtime(true) - ($query->time / 1000);

        $span = $this->agent->startSpan('SQL/Query', $startingTime);
        $span->tag('db.statement', $query->sql);
        $this->agent->stopSpan();
    }
}
