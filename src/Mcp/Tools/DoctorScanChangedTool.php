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

class DoctorScanChangedTool extends Tool
{
    protected string $name = 'doctor_scan_changed';

    protected string $title = 'Laravel Doctor Scan Changed';

    protected string $description = 'Run a read-only Laravel Doctor scan for changed files.';

    public function __construct(
        private readonly DoctorRequestFactory $factory,
        private readonly DoctorManager $manager,
        private readonly McpReportPresenter $presenter,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
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

        $doctorRequest = $this->factory->fromPayload(array_merge($arguments, [
            'scopePreset' => 'changed',
        ]));

        $result = McpReadOnlyScanner::scan($this->manager, $doctorRequest);

        return Response::structured($this->presenter->present($result));
    }
}
