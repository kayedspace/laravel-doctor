<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\DoctorManager;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;

/**
 * Runs a scan for MCP tools with report persistence disabled, keeping the MCP
 * surface read-only regardless of the project's doctor.reports.enabled setting.
 *
 * ponytail: config guard rather than a request-level flag; the whole MCP process
 * is read-only, so scoping the toggle around the single scan is enough.
 */
final class McpReadOnlyScanner
{
    public static function scan(DoctorManager $manager, DoctorRequest $request): DoctorReport
    {
        $previous = Config::get('doctor.reports.enabled', true);
        Config::set('doctor.reports.enabled', false);

        try {
            return $manager->scan($request);
        } finally {
            Config::set('doctor.reports.enabled', $previous);
        }
    }
}
