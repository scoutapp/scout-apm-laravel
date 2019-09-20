<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Scoutapm\Agent;
use Scoutapm\Laravel\Database\QueryListener;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use Scoutapm\Laravel\Middleware\IgnoredEndpoints;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Laravel\Middleware\SendRequestToScout;
use Scoutapm\Laravel\Providers\ScoutApmServiceProvider;
use Scoutapm\ScoutApmAgent;
use Throwable;
use function sprintf;

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

    /** @var MockInterface */
    private $dbFacadeMockery;

    public function setUp() : void
    {
        parent::setUp();

        $this->application = $this->createLaravelApplicationFulfillingBasicRequirementsForScout();

        $this->serviceProvider = new ScoutApmServiceProvider($this->application);
    }

    /** @throws Throwable */
    public function testScoutAgentIsRegistered() : void
    {
        self::assertFalse($this->application->has(ScoutApmAgent::class));
        self::assertFalse($this->application->has(Agent::class));
        self::assertFalse($this->application->has(self::SCOUTAPM_ALIAS_SERVICE_KEY));

        $this->serviceProvider->register();

        self::assertFalse($this->application->has(ScoutApmAgent::class));
        self::assertTrue($this->application->has(Agent::class));
        self::assertTrue($this->application->has(self::SCOUTAPM_ALIAS_SERVICE_KEY));
    }

    /** @throws Throwable */
    public function testViewEngineResolversHaveBeenWrapped() : void
    {
        $this->serviceProvider->register();

        /** @var EngineResolver $viewResolver */
        $viewResolver = $this->application->make('view.engine.resolver');

        $engine = $viewResolver->resolve('file');
        self::assertSame('Fake view engine for [file] - rendered path "/path/to/view"', $engine->get('/path/to/view'));

        /** @var Agent $agent */
        $agent = $this->application->make(ScoutApmAgent::class);
        self::assertArraySubset(
            [
                'BatchCommand' => [
                    'commands' => [
                        1 => [
                            'StartSpan' => ['operation' => 'View/test_template_name'],
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

        $this->dbFacadeMockery->shouldReceive('listen')
            ->once()
            ->with(Mockery::type(QueryListener::class));

        $this->bootServiceProvider();
    }

    private function bootServiceProvider() : void
    {
        $log = $this->application->make(LoggerInterface::class);
        $this->serviceProvider->boot(
            $this->application->make(Kernel::class),
            $this->application->make(ScoutApmAgent::class),
            $log
        );
    }

    /**
     * Helper to create a Laravel application instance that has very basic wiring up of services that our Laravel
     * binding library actually interacts with in some way.
     */
    private function createLaravelApplicationFulfillingBasicRequirementsForScout() : Application
    {
        $application = new Application();

        DB::clearResolvedInstances();
        $this->dbFacadeMockery = DB::spy();

        $application->singleton(
            LoggerInterface::class,
            static function () : LoggerInterface {
                return new TestLogger();
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
            function () : View {
                /** @var View&MockObject $viewMock */
                $viewMock = $this->createPartialMock(
                    View::class,
                    [
                        'getFinder',
                        'render',
                        'name',
                        'with',
                        'getData',
                    ]
                );

                /** @var FileViewFinder&MockObject $viewFinderMock */
                $viewFinderMock = $this->createMock(FileViewFinder::class);

                $viewFinderMock
                    ->method('getViews')
                    ->willReturn(['test_template_name' => '/path/to/view']);

                $viewMock->expects(self::once())
                    ->method('getFinder')
                    ->willReturn($viewFinderMock);

                return $viewMock;
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
