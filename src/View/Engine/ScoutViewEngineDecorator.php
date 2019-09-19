<?php

declare(strict_types=1);

namespace Scoutapm\Laravel\View\Engine;

use Illuminate\Contracts\View\Engine;
use Illuminate\View\Compilers\CompilerInterface;
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
        $this->agent      = $agent;
        $this->viewFinder = $viewFinder;
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $data = []) : string
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
     * Unfortunately, we have to disable all kinds of static analysis due to this violation :/
     *
     * @noinspection PhpUnused
     */
    public function getCompiler() : CompilerInterface
    {
        /**
         * @noinspection PhpUndefinedMethodInspection
         * @psalm-suppress UndefinedInterfaceMethod
         */
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
