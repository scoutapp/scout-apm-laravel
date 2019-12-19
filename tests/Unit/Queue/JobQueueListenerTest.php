<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\Laravel\Queue\JobQueueListener;
use Scoutapm\ScoutApmAgent;

/** @covers \Scoutapm\Laravel\Queue\JobQueueListener */
final class JobQueueListenerTest extends TestCase
{
    /** @var ScoutApmAgent&MockObject */
    private $agent;
    /** @var JobQueueListener */
    private $jobQueueListener;

    public function setUp() : void
    {
        parent::setUp();
        $this->agent = $this->createMock(ScoutApmAgent::class);

        $this->jobQueueListener = new JobQueueListener($this->agent);
    }

    public function testRequestIsReset() : void
    {
        $this->agent->expects(self::once())
            ->method('startNewRequest');

        $this->jobQueueListener->startNewRequestForJob();
    }

    /** @throws Exception */
    public function testSpanIsStarted() : void
    {
        $this->agent->expects(self::once())
            ->method('startSpan')
            ->with('Job/Foo');

        $job = $this->createMock(Job::class);
        $job->expects(self::once())
            ->method('resolveName')
            ->willReturn('Foo');

        $event = new JobProcessing('connection', $job);

        $this->jobQueueListener->startSpanForJob($event);
    }

    /** @throws Exception */
    public function testSpanIsStopped() : void
    {
        $this->agent->expects(self::once())
            ->method('stopSpan');

        $this->jobQueueListener->stopSpanForJob();
    }

    /** @throws Exception */
    public function testAgentConnectsAndSendsWhenRequestIsToBeSent() : void
    {
        $this->agent->expects(self::once())
            ->method('connect');

        $this->agent->expects(self::once())
            ->method('send');

        $this->jobQueueListener->sendRequestForJob();
    }
}
