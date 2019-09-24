<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\Events\Span\Span;
use Scoutapm\Laravel\Database\QueryListener;
use Scoutapm\ScoutApmAgent;

/** @covers \Scoutapm\Laravel\Database\QueryListener */
final class QueryListenerTest extends TestCase
{
    /** @var ScoutApmAgent|MockObject */
    private $agent;

    /** @var QueryListener */
    private $queryListener;

    public function setUp() : void
    {
        parent::setUp();

        $this->agent = $this->createMock(ScoutApmAgent::class);

        $this->queryListener = new QueryListener($this->agent);
    }

    public function testSqlQueryIsLogged() : void
    {
        $query = new QueryExecuted('SELECT 1', [], 1000, $this->createMock(Connection::class));

        /** @var Span&MockObject $spanMock */
        $spanMock = $this->createMock(Span::class);
        $spanMock->expects(self::once())
            ->method('tag')
            ->with('db.statement', 'SELECT 1');

        $this->agent->expects(self::once())
            ->method('startSpan')
            ->with('SQL/Query', self::isType(IsType::TYPE_FLOAT))
            ->willReturn($spanMock);

        $this->agent->expects(self::once())
            ->method('stopSpan');

        $this->queryListener->__invoke($query);
    }
}
