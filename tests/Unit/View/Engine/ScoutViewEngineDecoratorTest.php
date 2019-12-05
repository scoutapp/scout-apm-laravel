<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\View\Engine;

use Illuminate\Contracts\View\Engine;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Factory as ViewFactory;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\Laravel\View\Engine\ScoutViewEngineDecorator;
use Scoutapm\ScoutApmAgent;
use function uniqid;

/** @covers \Scoutapm\Laravel\View\Engine\ScoutViewEngineDecorator */
final class ScoutViewEngineDecoratorTest extends TestCase
{
    /** @var Engine&MockObject */
    private $realEngine;

    /** @var ScoutApmAgent&MockObject */
    private $agent;

    /** @var ViewFactory&MockObject */
    private $viewFactory;

    /** @var ScoutViewEngineDecorator */
    private $viewEngineDecorator;

    public function setUp() : void
    {
        parent::setUp();

        // Note: getCompiler is NOT a real method, it is implemented by the real implementation only, SOLID violation in Laravel
        $this->realEngine  = $this->createMock(EngineImplementationWithGetCompilerMethod::class);
        $this->agent       = $this->createMock(ScoutApmAgent::class);
        $this->viewFactory = $this->createMock(ViewFactory::class);

        $this->viewEngineDecorator = new ScoutViewEngineDecorator($this->realEngine, $this->agent, $this->viewFactory);
    }

    public function testGetWrapsCallToRealEngineInInstrumentation() : void
    {
        $viewTemplateName = uniqid('viewTemplateName', true);
        $path             = uniqid('path', true);
        $data             = ['foo' => 'bar'];
        $renderedString   = uniqid('renderedString', true);

        $this->viewFactory->expects(self::once())
            ->method('shared')
            ->with(ScoutViewEngineDecorator::VIEW_FACTORY_SHARED_KEY, 'unknown')
            ->willReturn($viewTemplateName);

        $this->agent
            ->expects(self::once())
            ->method('instrument')
            ->with('View', $viewTemplateName, self::isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(static function (string $type, string $name, callable $transaction) {
                return $transaction();
            });

        $this->realEngine->expects(self::once())
            ->method('get')
            ->with($path, $data)
            ->willReturn($renderedString);

        self::assertSame($renderedString, $this->viewEngineDecorator->get($path, $data));
    }

    public function testGetFallsBackToUnknownTemplateNameWhenPathWasNotDefined() : void
    {
        $path           = uniqid('path', true);
        $data           = ['foo' => 'bar'];
        $renderedString = uniqid('renderedString', true);

        $this->viewFactory->expects(self::once())
            ->method('shared')
            ->with(ScoutViewEngineDecorator::VIEW_FACTORY_SHARED_KEY, 'unknown')
            ->willReturn('unknown');

        $this->agent
            ->expects(self::once())
            ->method('instrument')
            ->with('View', 'unknown', self::isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(static function (string $type, string $name, callable $transaction) {
                return $transaction();
            });

        $this->realEngine->expects(self::once())
            ->method('get')
            ->with($path, $data)
            ->willReturn($renderedString);

        self::assertSame($renderedString, $this->viewEngineDecorator->get($path, $data));
    }

    public function testGetCompilerWillProxyToRealEngineGetCompilerMethd() : void
    {
        $compiler = $this->createMock(CompilerInterface::class);

        $this->realEngine->expects(self::once())
            ->method('getCompiler')
            ->willReturn($compiler);

        self::assertSame($compiler, $this->viewEngineDecorator->getCompiler());
    }
}
