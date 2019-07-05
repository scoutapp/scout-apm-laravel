<?php

namespace Scoutapm\Laravel\Commands;

use Illuminate\Console\Command;
use Scoutapm\Agent;
use Scoutapm\CoreAgentManager;

class DownloadCoreAgent extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:core-agent-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads and Starts the Scout Core Agent';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Agent $agent)
    {
        $cam = new CoreAgentManager($agent);
        $cam->launch();
    }
}

