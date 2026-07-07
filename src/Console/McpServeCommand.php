<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Server\Registrar;

class McpServeCommand extends Command
{
    protected $signature = 'doctor:mcp';

    protected $description = 'Start the local read-only Laravel Doctor MCP server';

    public function handle(Registrar $registrar): int
    {
        if (! (bool) Config::get('doctor.ai.mcp.enabled', false)) {
            $this->error('MCP server is disabled. Set doctor.ai.mcp.enabled to true to use doctor:mcp.');

            return 1;
        }

        $server = $registrar->getLocalServer('doctor');

        if ($server === null) {
            $this->error("MCP Server 'doctor' not found. Ensure it is registered correctly.");

            return 1;
        }

        $server();

        return 0;
    }
}
