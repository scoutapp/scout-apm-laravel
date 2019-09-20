<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Facades;

use PHPUnit\Framework\MockObject\MockObject;
use Scoutapm\Laravel\Facades\ScoutApm as ScoutApmFacade;
use PHPUnit\Framework\TestCase;
use Scoutapm\ScoutApmAgent;

/** @covers \Scoutapm\Laravel\Facades\ScoutApm */
final class ScoutApmTest extends TestCase
{
    /** @var ScoutApmAgent&MockObject */
    private $agent;

    public function setUp() : void
    {
        parent::setUp();

        $this->agent = $this->createMock(ScoutApmAgent::class);

        ScoutApmFacade::clearResolvedInstances();
        ScoutApmFacade::swap($this->agent);
    }

    public function proxiedMethodsProvider() : array
    {
        return [
            ['connect', []],
            ['enabled', []],
            ['startSpan', ['operation', null]],
            ['stopSpan', []],
            ['instrument', ['type', 'name', static function () {}]],
            ['webTransaction', ['name', static function () {}]],
            ['backgroundTransaction', ['name', static function () {}]],
            ['addContext', ['tag', 'value']],
            ['tagRequest', ['tag', 'value']],
            ['ignored', ['tag']],
            ['ignore', []],
            ['send', []],
        ];
    }

    /**
     * @param mixed[] $args
     * @dataProvider proxiedMethodsProvider
     */
    public function testFacadeProxiesMethodsToRealAgent(string $method, array $args) : void
    {
        $this->agent->expects(self::once())
            ->method($method)
            ->with(...$args);

        ScoutApmFacade::$method(...$args);
    }
}
