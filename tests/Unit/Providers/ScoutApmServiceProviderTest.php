<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\Providers;

use Closure;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel as HttpKernelInterface;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernelImplementation;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Router;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory as ViewFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionException;
use ReflectionProperty;
use Scoutapm\Agent;
use Scoutapm\Cache\DevNullCache;
use Scoutapm\Config;
use Scoutapm\Connector\Connector;
use Scoutapm\Events\Metadata;
use Scoutapm\Extension\PotentiallyAvailableExtensionCapabilities;
use Scoutapm\Laravel\Middleware\ActionInstrument;
use Scoutapm\Laravel\Middleware\IgnoredEndpoints;
use Scoutapm\Laravel\Middleware\MiddlewareInstrument;
use Scoutapm\Laravel\Middleware\SendRequestToScout;
use Scoutapm\Laravel\Providers\ScoutApmServiceProvider;
use Scoutapm\Laravel\View\Engine\ScoutViewEngineDecorator;
use Scoutapm\Logger\FilteredLogLevelDecorator;
use Scoutapm\ScoutApmAgent;
use Throwable;
use function json_decode;
use function json_encode;
use function putenv;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

/** @covers \Scoutapm\Laravel\Providers\ScoutApmServiceProvider */
final class ScoutApmServiceProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const CONFIG_SERVICE_KEY = ScoutApmAgent::class . '_config';
    private const CACHE_SERVICE_KEY  = ScoutApmAgent::class . '_cache';

    private const VIEW_ENGINES_TO_WRAP = ['file', 'php', 'blade'];

    /** @var Application&MockObject */
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

    /** @throws BindingResolutionException */
    public function testScoutAgentIsRegistered() : void
    {
        self::assertFalse($this->application->has(ScoutApmAgent::class));

        $this->serviceProvider->register();

        self::assertTrue($this->application->has(ScoutApmAgent::class));

        $agent = $this->application->make(ScoutApmAgent::class);

        self::assertInstanceOf(ScoutApmAgent::class, $agent);
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function testScoutAgentUsesLaravelCacheWhenConfigured() : void
    {
        $this->application->singleton('config', static function () {
            return new ConfigRepository([
                'cache' => [
                    'default' => 'array',
                    'stores' => [
                        'array' => ['driver' => 'array'],
                    ],
                ],
            ]);
        });

        $this->serviceProvider->register();
        $agent = $this->application->make(ScoutApmAgent::class);

        $cacheProperty = new ReflectionProperty($agent, 'cache');
        $cacheProperty->setAccessible(true);
        $cacheUsed = $cacheProperty->getValue($agent);

        self::assertInstanceOf(CacheRepository::class, $cacheUsed);
        self::assertInstanceOf(ArrayStore::class, $cacheUsed->getStore());
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function testScoutAgentUsesDevNullCacheWhenNoCacheIsConfigured() : void
    {
        $this->serviceProvider->register();
        $agent = $this->application->make(ScoutApmAgent::class);

        $cacheProperty = new ReflectionProperty($agent, 'cache');
        $cacheProperty->setAccessible(true);
        $cacheUsed = $cacheProperty->getValue($agent);

        self::assertInstanceOf(DevNullCache::class, $cacheUsed);
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function testScoutAgentPullsConfigFromConfigRepositoryAndEnv() : void
    {
        $configName = uniqid('configName', true);
        $configKey  = uniqid('configKey', true);

        putenv('SCOUT_KEY=' . $configKey);

        $this->application->singleton('config', static function () use ($configName) {
            return new ConfigRepository([
                'scout_apm' => [Config\ConfigKey::APPLICATION_NAME => $configName],
            ]);
        });

        $this->serviceProvider->register();

        /** @var ScoutApmAgent $agent */
        $agent = $this->application->make(ScoutApmAgent::class);

        $configProperty = new ReflectionProperty($agent, 'config');
        $configProperty->setAccessible(true);

        /** @var Config $configUsed */
        $configUsed = $configProperty->getValue($agent);

        self::assertSame($configName, $configUsed->get(Config\ConfigKey::APPLICATION_NAME));
        self::assertSame($configKey, $configUsed->get(Config\ConfigKey::APPLICATION_KEY));

        putenv('SCOUT_KEY');
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

        (new PotentiallyAvailableExtensionCapabilities())->clearRecordedCalls();
        $engine = $viewResolver->resolve('file');
        self::assertSame('Fake view engine for [file] - rendered path "/path/to/view"', $engine->get('/path/to/view'));

        /** @var Agent $agent */
        $agent = $this->application->make(ScoutApmAgent::class);

        $commands = $agent->getRequest()->jsonSerialize()['BatchCommand']['commands'];

        self::assertCount(4, $commands);

        self::assertArrayHasKey(1, $commands);
        self::assertArrayHasKey('StartSpan', $commands[1]);
        self::assertArrayHasKey('operation', $commands[1]['StartSpan']);
        self::assertSame('View/' . $templateName, $commands[1]['StartSpan']['operation']);
    }

    /** @throws Throwable */
    public function testMiddlewareAreRegisteredOnBootForHttpRequest() : void
    {
        /** @var HttpKernelImplementation $kernel */
        $kernel = $this->application->make(HttpKernelInterface::class);

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
    public function testMiddlewareAreNotRegisteredOnBootForConsoleRequest() : void
    {
        $this->application = $this->createLaravelApplicationFulfillingBasicRequirementsForScout(true);

        $this->serviceProvider = new ScoutApmServiceProvider($this->application);

        /** @var HttpKernelImplementation $kernel */
        $kernel = $this->application->make(HttpKernelInterface::class);

        $this->serviceProvider->register();

        self::assertFalse($kernel->hasMiddleware(MiddlewareInstrument::class));
        self::assertFalse($kernel->hasMiddleware(ActionInstrument::class));
        self::assertFalse($kernel->hasMiddleware(IgnoredEndpoints::class));
        self::assertFalse($kernel->hasMiddleware(SendRequestToScout::class));

        $this->bootServiceProvider();

        self::assertFalse($kernel->hasMiddleware(MiddlewareInstrument::class));
        self::assertFalse($kernel->hasMiddleware(ActionInstrument::class));
        self::assertFalse($kernel->hasMiddleware(IgnoredEndpoints::class));
        self::assertFalse($kernel->hasMiddleware(SendRequestToScout::class));
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

    /** @throws Throwable */
    public function testJobQueueIsInstrumentedWhenRunningInConsole() : void
    {
        $this->application     = $this->createLaravelApplicationFulfillingBasicRequirementsForScout(true);
        $this->serviceProvider = new ScoutApmServiceProvider($this->application);
        $this->serviceProvider->register();

        $this->application->singleton(
            ScoutApmAgent::class,
            function () : ScoutApmAgent {
                return $this->createMock(ScoutApmAgent::class);
            }
        );

        /** @var Agent&MockObject $agent */
        $agent = $this->application->make(ScoutApmAgent::class);

        $agent->expects(self::once())
            ->method('shouldInstrument')
            ->with(ScoutApmServiceProvider::INSTRUMENT_LARAVEL_QUEUES)
            ->willReturn(true);

        $agent->expects(self::once())
            ->method('startNewRequest');

        $agent->expects(self::once())
            ->method('startSpan');

        $agent->expects(self::once())
            ->method('stopSpan');

        $agent->expects(self::once())
            ->method('connect');

        $agent->expects(self::once())
            ->method('send');

        $this->bootServiceProvider();

        /** @var Dispatcher $events */
        $events = $this->application->make('events');

        $events->dispatch(new JobProcessing('foo', $this->createMock(Job::class)));
        $events->dispatch(new JobProcessed('foo', $this->createMock(Job::class)));
    }

    /** @throws Throwable */
    public function testJobQueueIsInstrumentedWhenRunningInHttp() : void
    {
        $this->serviceProvider->register();

        $this->application->singleton(
            ScoutApmAgent::class,
            function () : ScoutApmAgent {
                return $this->createMock(ScoutApmAgent::class);
            }
        );

        /** @var Agent&MockObject $agent */
        $agent = $this->application->make(ScoutApmAgent::class);

        $agent->expects(self::once())
            ->method('shouldInstrument')
            ->with(ScoutApmServiceProvider::INSTRUMENT_LARAVEL_QUEUES)
            ->willReturn(true);

        $agent->expects(self::never())
            ->method('startNewRequest');

        $agent->expects(self::once())
            ->method('startSpan');

        $agent->expects(self::once())
            ->method('stopSpan');

        $agent->expects(self::never())
            ->method('connect');

        $agent->expects(self::never())
            ->method('send');

        $this->bootServiceProvider();

        /** @var Dispatcher $events */
        $events = $this->application->make('events');

        $events->dispatch(new JobProcessing('foo', $this->createMock(Job::class)));
        $events->dispatch(new JobProcessed('foo', $this->createMock(Job::class)));
    }

    /** @throws Throwable */
    public function testJobQueuesAreNotInstrumentedWhenNotConfigured() : void
    {
        $this->application     = $this->createLaravelApplicationFulfillingBasicRequirementsForScout(true);
        $this->serviceProvider = new ScoutApmServiceProvider($this->application);

        $this->application->singleton('config', static function () {
            return new ConfigRepository([
                'scout_apm' => [
                    Config\ConfigKey::DISABLED_INSTRUMENTS => [ScoutApmServiceProvider::INSTRUMENT_LARAVEL_QUEUES],
                ],
            ]);
        });

        $this->serviceProvider->register();

        $this->application->singleton(
            ScoutApmAgent::class,
            function () : ScoutApmAgent {
                return $this->createMock(ScoutApmAgent::class);
            }
        );

        /** @var Agent&MockObject $agent */
        $agent = $this->application->make(ScoutApmAgent::class);

        $agent->expects(self::once())
            ->method('shouldInstrument')
            ->with(ScoutApmServiceProvider::INSTRUMENT_LARAVEL_QUEUES)
            ->willReturn(false);

        $agent->expects(self::never())
            ->method('startNewRequest');

        $agent->expects(self::never())
            ->method('startSpan');

        $agent->expects(self::never())
            ->method('stopSpan');

        $agent->expects(self::never())
            ->method('connect');

        $agent->expects(self::never())
            ->method('send');

        $this->bootServiceProvider();

        /** @var Dispatcher $events */
        $events = $this->application->make('events');

        $events->dispatch(new JobProcessing('foo', $this->createMock(Job::class)));
        $events->dispatch(new JobProcessed('foo', $this->createMock(Job::class)));
    }

    /** @throws BindingResolutionException */
    public function testMetadataContainsFrameworkNameAndVersion() : void
    {
        $connectorMock = $this->createMock(Connector::class);

        $this->application->singleton('config', static function () {
            return new ConfigRepository([
                'scout_apm' => [
                    Config\ConfigKey::APPLICATION_NAME => 'Laravel Provider Test',
                    Config\ConfigKey::APPLICATION_KEY => 'test application key',
                    Config\ConfigKey::MONITORING_ENABLED => true,
                ],
            ]);
        });

        $this->serviceProvider->register();

        $this->application->singleton(ScoutApmAgent::class, function () use ($connectorMock) {
            return Agent::fromConfig(
                $this->application->make(self::CONFIG_SERVICE_KEY),
                $this->application->make(FilteredLogLevelDecorator::class),
                $this->application->make(self::CACHE_SERVICE_KEY),
                $connectorMock
            );
        });

        $this->bootServiceProvider();

        $connectorMock->expects(self::at(3))
            ->method('sendCommand')
            ->with(self::callback(static function (Metadata $metadata) {
                $flattenedMetadata = json_decode(json_encode($metadata), true)['ApplicationEvent']['event_value'];

                self::assertArrayHasKey('framework', $flattenedMetadata);
                self::assertSame('Laravel', $flattenedMetadata['framework']);

                self::assertArrayHasKey('framework_version', $flattenedMetadata);
                self::assertNotSame('', $flattenedMetadata['framework_version']);

                return true;
            }));

        /** @var ScoutApmAgent $agent */
        $agent = $this->application->make(ScoutApmAgent::class);
        $agent->send();
    }

    /** @throws BindingResolutionException */
    private function bootServiceProvider() : void
    {
        $log = $this->application->make(FilteredLogLevelDecorator::class);
        $this->serviceProvider->boot(
            $this->application,
            $this->application->make(ScoutApmAgent::class),
            $log,
            $this->connection
        );
    }

    /**
     * Helper to create a Laravel application instance that has very basic wiring up of services that our Laravel
     * binding library actually interacts with in some way.
     */
    private function createLaravelApplicationFulfillingBasicRequirementsForScout(bool $runningInConsole = false) : Application
    {
        $application = $this->getMockBuilder(Application::class)
            ->setMethods(['runningInConsole'])
            ->getMock();

        $application
            ->method('runningInConsole')
            ->willReturn($runningInConsole);

        $application->singleton(
            LoggerInterface::class,
            function () : LoggerInterface {
                return $this->createMock(LoggerInterface::class);
            }
        );
        $application->alias(LoggerInterface::class, 'log');

        $application->singleton(
            FilteredLogLevelDecorator::class,
            static function () use ($application) : FilteredLogLevelDecorator {
                return new FilteredLogLevelDecorator(
                    $application->make(LoggerInterface::class),
                    LogLevel::DEBUG
                );
            }
        );

        $application->singleton(
            HttpKernelInterface::class,
            function () use ($application) : HttpKernelInterface {
                return new HttpKernelImplementation($application, $this->createMock(Router::class));
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

        $application->singleton(
            'cache',
            static function () use ($application) : CacheManager {
                return new CacheManager($application);
            }
        );

        // Older versions of Laravel used `path.config` service name for path...
        $application->singleton(
            'path.config',
            static function () : string {
                return sys_get_temp_dir();
            }
        );

        $application->singleton(
            'config',
            static function () : ConfigRepository {
                return new ConfigRepository();
            }
        );

        return $application;
    }
}
