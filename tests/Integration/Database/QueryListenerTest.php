<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\IntegrationTests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Scoutapm\Agent;
use Scoutapm\Cache\DevNullCache;
use Scoutapm\Config;
use Scoutapm\Config\ConfigKey;
use Scoutapm\Connector\Connector;
use Scoutapm\Events\Request\Request;
use Scoutapm\Extension\PotentiallyAvailableExtensionCapabilities;
use Scoutapm\Laravel\Database\QueryListener;
use function array_column;
use function array_key_exists;
use function extension_loaded;
use function implode;
use function json_decode;
use function json_encode;
use function uniqid;

/** @covers \Scoutapm\Laravel\Database\QueryListener */
final class QueryListenerTest extends TestCase
{
    public function testControllerTimeDoesNotBecomeNegativeWhenExtensionEnabled() : void
    {
        if (! extension_loaded('scoutapm')) {
            self::markTestSkipped('scoutapm extension must be enabled for this test');
        }

        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite extension must be enabled for this test');
        }

        $connector    = $this->createMock(Connector::class);
        $phpExtension = new PotentiallyAvailableExtensionCapabilities();

        $scout = Agent::fromConfig(
            Config::fromArray([
                ConfigKey::APPLICATION_NAME => 'My test app',
                ConfigKey::APPLICATION_KEY => uniqid('applicationKey', true),
                ConfigKey::MONITORING_ENABLED => true,
                ConfigKey::CORE_AGENT_DOWNLOAD_ENABLED => false,
                ConfigKey::CORE_AGENT_LAUNCH_ENABLED => false,
            ]),
            new TestLogger(),
            new DevNullCache(),
            $connector,
            $phpExtension
        );

        $laravelConnection = new Connection(new PDO('sqlite::memory:'));
        $laravelConnection->setEventDispatcher(new Dispatcher());
        $laravelConnection->listen(static function (QueryExecuted $query) use ($scout) : void {
            (new QueryListener($scout))->__invoke($query);
        });

        $phpExtension->clearRecordedCalls();

        $scout->webTransaction(
            'MyController',
            static function () use ($laravelConnection) : void {
                self::assertEquals(
                    [
                        (object) ['1' => '1'],
                    ],
                    $laravelConnection->select('SELECT 1')
                );
            }
        );

        $connector
            ->expects(self::at(4))
            ->method('sendCommand')
            ->with(self::callback(function (Request $request) : bool {
                $payload = json_decode(json_encode($request), true)['BatchCommand']['commands'];

                $controllerStartSpan = $this->findCommands($payload, 'StartSpan', 'operation', 'Controller/MyController')[0];

                $childSpans = $this->findCommands($payload, 'StartSpan', 'parent_id', $controllerStartSpan['span_id']);

                $childSpanOperations = implode(', ', array_column($childSpans, 'operation'));

                self::assertCount(1, $childSpans, 'Found multiple child spans: ' . $childSpanOperations);
                self::assertSame('SQL/Query', $childSpanOperations);

                return true;
            }));

        $scout->send();
    }

    /**
     * @param string[][] $commands
     *
     * @return string[][]
     */
    private function findCommands(array $commands, string $type, string $key, string $value) : array
    {
        $matching = [];

        foreach ($commands as $command) {
            if (! array_key_exists($type, $command)
                || ! array_key_exists($key, $command[$type])
                || $command[$type][$key] !== $value
            ) {
                continue;
            }

            $matching[] = $command[$type];
        }

        return $matching;
    }
}
