<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Logger\FilteredLogLevelDecorator;
use Scoutapm\ScoutApmAgent;

/** @covers \Scoutapm\Laravel\Middleware\MiddlewareInstrument */
final class MiddlewareInstrumentTest extends TestCase
{
    /** @var ScoutApmAgent&MockObject */
    private $agent;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var MiddlewareInstrument */
    private $middleware;

    public function setUp() : void
    {
        parent::setUp();

        $this->agent  = $this->createMock(ScoutApmAgent::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->middleware = new MiddlewareInstrument(
            $this->agent,
            new FilteredLogLevelDecorator($this->logger, LogLevel::DEBUG)
        );
    }

    public function testHandleWrappsMiddlewareExecutionInInstrumentation() : void
    {
        $expectedResponse = new Response();

        $this->agent
            ->expects(self::once())
            ->method('instrument')
            ->with('Middleware', 'all', self::isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(static function (string $type, string $name, callable $transaction) {
                return $transaction();
            });

        $this->logger->expects(self::once())
            ->method('log')
            ->with(LogLevel::DEBUG, '[Scout] Handle MiddlewareInstrument');

        self::assertSame(
            $expectedResponse,
            $this->middleware->handle(
                new Request(),
                static function () use ($expectedResponse) {
                    return $expectedResponse;
                }
            )
        );
    }
}
