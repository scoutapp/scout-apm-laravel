<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Facades;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\Laravel\Facades\ScoutApm as ScoutApmFacade;
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

    /**
     * @return string[][]|string[][][]|null[][][]|callable[][][]
     */
    public function proxiedMethodsProvider() : array
    {
        $callable = static function () : void {
        };

        return [
            ['connect', []],
            ['enabled', []],
            ['startSpan', ['operation', null]],
            ['stopSpan', []],
            ['instrument', ['type', 'name', $callable]],
            ['webTransaction', ['name', $callable]],
            ['backgroundTransaction', ['name', $callable]],
            ['addContext', ['tag', 'value']],
            ['tagRequest', ['tag', 'value']],
            ['ignored', ['tag']],
            ['ignore', []],
            ['send', []],
        ];
    }

    /**
     * @param mixed[] $args
     *
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
