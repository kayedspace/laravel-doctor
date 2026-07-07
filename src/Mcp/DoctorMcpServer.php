<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Mcp\Tools\DoctorExplainRuleTool;
use kayedspace\Doctor\Mcp\Tools\DoctorListRulesTool;
use kayedspace\Doctor\Mcp\Tools\DoctorResolvePlanTool;
use kayedspace\Doctor\Mcp\Tools\DoctorScanChangedTool;
use kayedspace\Doctor\Mcp\Tools\DoctorScanFilesTool;
use kayedspace\Doctor\Mcp\Tools\DoctorScanTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;

#[Name('Laravel Doctor')]
#[Version('dev')]
#[Instructions('Read-only Laravel Doctor diagnostics over the existing scan engine.')]
class DoctorMcpServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        DoctorScanTool::class,
        DoctorScanFilesTool::class,
        DoctorScanChangedTool::class,
        DoctorListRulesTool::class,
        DoctorExplainRuleTool::class,
        DoctorResolvePlanTool::class,
    ];

    /**
     * Restrict exposed tools to the configured allow-list (empty = all).
     */
    protected function boot(): void
    {
        $allow = Config::get('doctor.ai.mcp.tools', []);

        if (! is_array($allow) || $allow === []) {
            return;
        }

        $this->tools = array_values(array_filter(
            $this->tools,
            static fn (string $tool): bool => in_array(
                (new ReflectionClass($tool))->getDefaultProperties()['name'] ?? null,
                $allow,
                true,
            ),
        ));
    }
}
