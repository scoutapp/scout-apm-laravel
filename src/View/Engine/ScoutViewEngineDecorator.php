<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\View\Engine;

use Illuminate\Contracts\View\Engine;
use Illuminate\View\FileViewFinder;
use Scoutapm\Agent;
use function array_search;

/** @noinspection ContractViolationInspection */
final class ScoutViewEngineDecorator implements Engine
{
    /** @var Engine */
    private $realEngine;

    /** @var Agent */
    private $agent;

    /** @var FileViewFinder */
    private $viewFinder;

    public function __construct(Engine $realEngine, Agent $agent, FileViewFinder $viewFinder)
    {
        $this->realEngine = $realEngine;
        $this->agent = $agent;
        $this->viewFinder = $viewFinder;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        return $this->agent->instrument(
            'View',
            $this->determineTemplateNameFromPath($path),
            function () use ($path, $data) {
                return $this->realEngine->get($path, $data);
            }
        );
    }

    /**
     * Since Laravel has a nasty habit of exposing public API that is not defined in interfaces, we must expose the
     * getCompiler method commonly used in the actual view engines.
     *
     * @noinspection PhpUnused
     */
    public function getCompiler()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->realEngine->getCompiler();
    }

    private function determineTemplateNameFromPath(string $path) : string
    {
        $templateName = array_search($path, $this->viewFinder->getViews(), true);

        if ($templateName === false) {
            return 'unknown';
        }

        return $templateName;
    }
}
