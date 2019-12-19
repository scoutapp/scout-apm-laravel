<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\Queue;

use Exception;
use Illuminate\Queue\Events\JobProcessing;
use Scoutapm\Events\Span\Span;
use Scoutapm\ScoutApmAgent;
use function class_basename;
use function sprintf;

final class JobQueueListener
{
    /** @var ScoutApmAgent */
    private $agent;

    public function __construct(ScoutApmAgent $agent)
    {
        $this->agent = $agent;
    }

    public function startNewRequestForJob() : void
    {
        $this->agent->startNewRequest();
    }

    /** @throws Exception */
    public function startSpanForJob(JobProcessing $jobProcessingEvent) : void
    {
        /** @noinspection UnusedFunctionResultInspection */
        $this->agent->startSpan(sprintf(
            '%s/%s',
            Span::INSTRUMENT_JOB,
            class_basename($jobProcessingEvent->job->resolveName())
        ));
    }

    /** @throws Exception */
    public function stopSpanForJob() : void
    {
        $this->agent->stopSpan();
    }

    /** @throws Exception */
    public function sendRequestForJob() : void
    {
        $this->agent->connect();
        $this->agent->send();
    }
}
