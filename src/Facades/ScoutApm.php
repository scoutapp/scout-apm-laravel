<?php
declare(strict_types=1);

namespace Scoutapm\Laravel\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Scoutapm\Events\Span\Span;

/**
 * @method static void connect()
 * @method static bool enabled()
 * @method static Span startSpan(string $operation, ?float $overrideTimestamp = null)
 * @method static void stopSpan()
 * @method static mixed instrument(string $type, string $name, Closure $block)
 * @method static mixed webTransaction(string $name, Closure $block)
 * @method static mixed backgroundTransaction(string $name, Closure $block)
 * @method static void addContext(string $tag, string $value)
 * @method static void tagRequest(string $tag, string $value)
 * @method static bool ignored(string $path)
 * @method static void ignore()
 * @method static bool send()
 *
 * @see \Scoutapm\Agent
 */
class ScoutApm extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'scoutapm';
    }
}
