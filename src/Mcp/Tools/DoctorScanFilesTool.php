<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use kayedspace\Doctor\DoctorManager;
use kayedspace\Doctor\Http\Support\DoctorRequestFactory;
use kayedspace\Doctor\Mcp\McpArgumentValidator;
use kayedspace\Doctor\Mcp\McpReportPresenter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DoctorScanFilesTool extends Tool
{
    protected string $name = 'doctor_scan_files';

    protected string $title = 'Laravel Doctor Scan Files';

    protected string $description = 'Run a read-only Laravel Doctor scan for project-relative files.';

    public function __construct(
        private readonly DoctorRequestFactory $factory,
        private readonly DoctorManager $manager,
        private readonly McpReportPresenter $presenter,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'paths' => $schema->array()
                ->items($schema->string())
                ->description('The project-relative file paths to scan.')
                ->required(),
        ];
    }

    public function handle(Request $request): mixed
    {
        $arguments = $request->all();
        McpArgumentValidator::assertSafe($arguments);

        $paths = $arguments['paths'] ?? [];
        if (! is_array($paths)) {
            throw new \InvalidArgumentException('Invalid MCP argument: paths must be an array.');
        }

        $doctorRequest = $this->factory->fromPayload([
            'scopePreset' => 'manual',
            'paths' => $paths,
            'saveReport' => false,
        ]);

        $result = $this->manager->scan($doctorRequest);

        return Response::structured($this->presenter->present($result));
    }
}
