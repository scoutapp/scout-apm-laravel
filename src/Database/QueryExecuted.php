<?php declare(strict_types=1);

namespace Illuminate\Database\Events;

/**
 * Class QueryExecuted
 *
 * @package Scoutapm\Laravel\Database
 */
class QueryExecuted
{
    public $sql;
    public $bindings;
    public $time;

    public function __construct($sql, $bindings, $time)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->time = $time;
    }
}
