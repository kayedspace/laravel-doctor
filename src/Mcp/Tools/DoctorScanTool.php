<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use kayedspace\Doctor\DoctorManager;
use kayedspace\Doctor\Http\Support\DoctorRequestFactory;
use kayedspace\Doctor\Mcp\McpArgumentValidator;
use kayedspace\Doctor\Mcp\McpReadOnlyScanner;
use kayedspace\Doctor\Mcp\McpReportPresenter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DoctorScanTool extends Tool
{
    protected string $name = 'doctor_scan';

    protected string $title = 'Laravel Doctor Scan';

    protected string $description = 'Run a read-only Laravel Doctor scan.';

    public function __construct(
        private readonly DoctorRequestFactory $factory,
        private readonly DoctorManager $manager,
        private readonly McpReportPresenter $presenter,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'scopePreset' => $schema->string()
                ->enum(['full', 'manual', 'changed', 'laravel'])
                ->description('Scan scope. Default "full"; "manual" scans the given paths; "changed" scans git-changed files; "laravel" scans the app/config/database/routes/views tree.'),
            'paths' => $schema->array()->items($schema->string())
                ->description('Project-relative paths to scan (used when scopePreset is "manual").'),
            'rules' => $schema->array()->items($schema->string())
                ->description('Only run these rule ids.'),
            'packs' => $schema->array()->items($schema->string())
                ->description('Only run rules from these packs.'),
            'exclusions' => $schema->array()->items($schema->string())
                ->description('Glob patterns to exclude from the scan.'),
            'booted' => $schema->boolean()
                ->description('Boot the application to run booted/runtime rules.'),
            'probePaths' => $schema->array()->items($schema->string())
                ->description('Runtime health-probe URLs or paths.'),
            'auditDependencies' => $schema->boolean()
                ->description('Include the Composer dependency audit.'),
        ];
    }

    public function handle(Request $request): mixed
    {
        $arguments = $request->all();
        McpArgumentValidator::assertSafe($arguments);

        $doctorRequest = $this->factory->fromPayload($arguments);
        $result = McpReadOnlyScanner::scan($this->manager, $doctorRequest);

        return Response::structured($this->presenter->present($result));
    }
}
