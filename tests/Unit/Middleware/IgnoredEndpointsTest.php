<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\Laravel\Middleware\IgnoredEndpoints;
use Scoutapm\ScoutApmAgent;

/** @covers \Scoutapm\Laravel\Middleware\IgnoredEndpoints */
final class IgnoredEndpointsTest extends TestCase
{
    /** @var ScoutApmAgent&MockObject */
    private $agent;

    /** @var IgnoredEndpoints */
    private $middleware;

    public function setUp() : void
    {
        parent::setUp();

        $this->agent = $this->createMock(ScoutApmAgent::class);

        $this->middleware = new IgnoredEndpoints($this->agent);
    }

    public function testHandleIgnoresPathIfItIsIgnored() : void
    {
        $request          = new Request();
        $expectedResponse = new Response();

        $this->agent->expects(self::once())
            ->method('ignored')
            ->with('/' . $request->path())
            ->willReturn(true);

        $this->agent->expects(self::once())
            ->method('ignore');

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

    public function testHandleDoesNothingIfPathIsNotIgnored() : void
    {
        $request          = new Request();
        $expectedResponse = new Response();

        $this->agent->expects(self::once())
            ->method('ignored')
            ->with('/' . $request->path())
            ->willReturn(false);

        $this->agent->expects(self::never())
            ->method('ignore');

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
