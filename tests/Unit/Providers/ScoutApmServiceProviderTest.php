<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Providers;

use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory as ViewFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Scoutapm\Agent;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use Scoutapm\Laravel\Middleware\IgnoredEndpoints;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Laravel\Middleware\SendRequestToScout;
use Scoutapm\Laravel\Providers\ScoutApmServiceProvider;
use Scoutapm\Laravel\View\Engine\ScoutViewEngineDecorator;
use Scoutapm\ScoutApmAgent;
use Throwable;
use function sprintf;
use function uniqid;

/** @covers \Scoutapm\Laravel\Providers\ScoutApmServiceProvider */
final class ScoutApmServiceProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SCOUTAPM_ALIAS_SERVICE_KEY = 'scoutapm';

    private const VIEW_ENGINES_TO_WRAP = ['file', 'php', 'blade'];

    /** @var Application */
    private $application;

    /** @var ScoutApmServiceProvider */
    private $serviceProvider;

    /** @var Connection&MockObject */
    private $connection;

    public function setUp() : void
    {
        parent::setUp();

        $this->application = $this->createLaravelApplicationFulfillingBasicRequirementsForScout();
        $this->connection  = $this->createMock(Connection::class);

        $this->serviceProvider = new ScoutApmServiceProvider($this->application);
    }

    /** @throws Throwable */
    public function testScoutAgentIsRegistered() : void
    {
        self::assertFalse($this->application->has(ScoutApmAgent::class));
        self::assertFalse($this->application->has(Agent::class));
        self::assertFalse($this->application->has(self::SCOUTAPM_ALIAS_SERVICE_KEY));

        $this->serviceProvider->register();

        self::assertTrue($this->application->has(ScoutApmAgent::class));
        self::assertTrue($this->application->has(Agent::class));
        self::assertTrue($this->application->has(self::SCOUTAPM_ALIAS_SERVICE_KEY));
    }

    /** @throws Throwable */
    public function testViewEngineResolversHaveBeenWrapped() : void
    {
        $this->serviceProvider->register();

        $templateName = uniqid('test_template_name', true);

        /** @var EngineResolver $viewResolver */
        $viewResolver = $this->application->make('view.engine.resolver');

        /** @var ViewFactory&MockObject $viewFactory */
        $viewFactory = $this->application->make('view');
        $viewFactory->expects(self::once())
            ->method('composer')
            ->with('*', self::isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(function (string $whichViews, callable $composer) use ($templateName) : void {
                /** @var View&MockObject $mockView */
                $mockView = $this->createMock(View::class);
                $mockView->expects(self::once())
                    ->method('name')
                    ->willReturn($templateName);
                $composer($mockView);
            });

        $viewFactory->expects(self::once())
            ->method('share')
            ->with(ScoutViewEngineDecorator::VIEW_FACTORY_SHARED_KEY, $templateName);

        $viewFactory->expects(self::once())
            ->method('shared')
            ->with(ScoutViewEngineDecorator::VIEW_FACTORY_SHARED_KEY, 'unknown')
            ->willReturn($templateName);

        $engine = $viewResolver->resolve('file');
        self::assertSame('Fake view engine for [file] - rendered path "/path/to/view"', $engine->get('/path/to/view'));

        /** @var Agent $agent */
        $agent = $this->application->make(ScoutApmAgent::class);
        self::assertArraySubset(
            [
                'BatchCommand' => [
                    'commands' => [
                        1 => [
                            'StartSpan' => ['operation' => 'View/' . $templateName],
                        ],
                    ],
                ],
            ],
            $agent->getRequest()->jsonSerialize()
        );
    }

    /** @throws Throwable */
    public function testMiddlewareAreRegisteredOnBoot() : void
    {
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->application->make(Kernel::class);

        $this->serviceProvider->register();

        self::assertFalse($kernel->hasMiddleware(MiddlewareInstrument::class));
        self::assertFalse($kernel->hasMiddleware(ActionInstrument::class));
        self::assertFalse($kernel->hasMiddleware(IgnoredEndpoints::class));
        self::assertFalse($kernel->hasMiddleware(SendRequestToScout::class));

        $this->bootServiceProvider();

        self::assertTrue($kernel->hasMiddleware(MiddlewareInstrument::class));
        self::assertTrue($kernel->hasMiddleware(ActionInstrument::class));
        self::assertTrue($kernel->hasMiddleware(IgnoredEndpoints::class));
        self::assertTrue($kernel->hasMiddleware(SendRequestToScout::class));
    }

    /** @throws Throwable */
    public function testDatabaseQueryListenerIsRegistered() : void
    {
        $this->serviceProvider->register();

        $this->connection->expects(self::once())
            ->method('listen')
            ->with(self::isInstanceOf(Closure::class));

        $this->bootServiceProvider();
    }

    private function bootServiceProvider() : void
    {
        $log = $this->application->make(LoggerInterface::class);
        $this->serviceProvider->boot(
            $this->application->make(Kernel::class),
            $this->application->make(ScoutApmAgent::class),
            $log,
            $this->connection
        );
    }

    /**
     * Helper to create a Laravel application instance that has very basic wiring up of services that our Laravel
     * binding library actually interacts with in some way.
     */
    private function createLaravelApplicationFulfillingBasicRequirementsForScout() : Application
    {
        $application = new Application();

        $application->singleton(
            LoggerInterface::class,
            function () : LoggerInterface {
                return $this->createMock(LoggerInterface::class);
            }
        );
        $application->alias(LoggerInterface::class, 'log');

        $application->singleton(
            Kernel::class,
            function () use ($application) : Kernel {
                return new \Illuminate\Foundation\Http\Kernel($application, $this->createMock(Router::class));
            }
        );

        $application->singleton(
            'view',
            function () : ViewFactory {
                return $this->createMock(ViewFactory::class);
            }
        );

        $application->singleton(
            'view.engine.resolver',
            function () : EngineResolver {
                $viewEngineResolver = new EngineResolver();

                foreach (self::VIEW_ENGINES_TO_WRAP as $viewEngineName) {
                    $viewEngineResolver->register(
                        $viewEngineName,
                        function () use ($viewEngineName) : Engine {
                            return new class ($viewEngineName) implements Engine {
                                /** @var string */
                                private $viewEngineName;

                                public function __construct(string $viewEngineName)
                                {
                                    $this->viewEngineName = $viewEngineName;
                                }

                                /** @inheritDoc */
                                public function get($path, array $data = []) : string
                                {
                                    return sprintf(
                                        'Fake view engine for [%s] - rendered path "%s"',
                                        $this->viewEngineName,
                                        $path
                                    );
                                }
                            };
                        }
                    );
                }

                return $viewEngineResolver;
            }
        );

        return $application;
    }
}
