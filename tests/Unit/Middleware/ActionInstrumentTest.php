<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Mockery\Mock;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Scoutapm\Events\Span\Span;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use PHPUnit\Framework\TestCase;
use Scoutapm\ScoutApmAgent;
use Throwable;

/** @covers \Scoutapm\Laravel\Middleware\ActionInstrument */
final class ActionInstrumentTest extends TestCase
{
    /** @var ScoutApmAgent&MockObject */
    private $agent;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var Router&MockObject */
    private $router;

    /** @var Span&MockObject */
    private $span;

    /** @var ActionInstrument */
    private $middleware;

    public function setUp() : void
    {
        parent::setUp();

        $this->agent = $this->createMock(ScoutApmAgent::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->router = $this->createMock(Router::class);
        $this->span = $this->createMock(Span::class);

        $this->middleware = new ActionInstrument($this->agent, $this->logger, $this->router);

        $this->agent
            ->expects(self::once())
            ->method('webTransaction')
            ->with('unknown', self::isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(function (string $originalName, callable $transaction) {
                return $transaction($this->span);
            });
    }

    /** @throws Throwable */
    public function testHandleRecordsControllerNameWhenRouteHasControllerKey() : void
    {
        $expectedResponse = new Response();

        $controllerName = uniqid('controllerName', true);

        $this->router->expects(self::once())
            ->method('current')
            ->willReturn(new Route('GET', '/default-url', ['controller' => $controllerName]));

        $this->span->expects(self::once())
            ->method('updateName')
            ->with('Controller/' . $controllerName);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('[Scout] Handle ActionInstrument');

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

    /** @throws Throwable */
    public function testHandleRecordsControllerNameWhenRouteDoesNotHaveAControllerKey() : void
    {
        $expectedResponse = new Response();

        $url = uniqid('url', true);

        $this->router->expects(self::once())
            ->method('current')
            ->willReturn(new Route('GET', $url, []));

        $this->span->expects(self::once())
            ->method('updateName')
            ->with('Controller/' . $url);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('[Scout] Handle ActionInstrument');

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

    /** @throws Throwable */
    public function testHandleRecordsUnknownControllerNameWhenNoRouteFound() : void
    {
        $expectedResponse = new Response();

        $this->router->expects(self::once())
            ->method('current')
            ->willReturn(null);

        $this->span->expects(self::once())
            ->method('updateName')
            ->with('Controller/unknown');

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('[Scout] Handle ActionInstrument');

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

    /** @throws Throwable */
    public function testHandleRecordsUnknownControllerNameWhenRouterCurrentThrowsException() : void
    {
        $expectedResponse = new Response();

        $this->router->expects(self::once())
            ->method('current')
            ->willThrowException(new \Exception('oh no'));

        $this->span->expects(self::once())
            ->method('updateName')
            ->with('Controller/unknown');

        $this->logger->expects(self::exactly(2))
            ->method('debug')
            ->with(self::logicalOr(
                '[Scout] Handle ActionInstrument',
                '[Scout] Exception obtaining name of endpoint: oh no'
            ));

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
