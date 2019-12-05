<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\UnitTests\View\Engine;

use Illuminate\Contracts\View\Engine;

class EngineImplementationWithGetCompilerMethod implements Engine
{
    public function get($path, array $data = [])
    {
    }

    public function getCompiler()
    {
    }
}
