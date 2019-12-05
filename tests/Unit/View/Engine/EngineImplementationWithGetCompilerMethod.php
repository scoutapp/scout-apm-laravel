<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\View\Engine;

use Illuminate\Contracts\View\Engine;

class EngineImplementationWithGetCompilerMethod implements Engine
{
    /** @inheritDoc */
    public function get($path, array $data = [])
    {
    }

    /** @inheritDoc */
    public function getCompiler()
    {
    }
}
