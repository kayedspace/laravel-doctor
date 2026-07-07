<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use kayedspace\Doctor\Rules\RuleCatalog;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DoctorListRulesTool extends Tool
{
    protected string $name = 'doctor_list_rules';

    protected string $title = 'Laravel Doctor List Rules';

    protected string $description = 'List Laravel Doctor rules and metadata.';

    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): mixed
    {
        return Response::structured(['rules' => $this->catalog->all()]);
    }
}
