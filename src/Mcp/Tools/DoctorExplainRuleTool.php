<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use kayedspace\Doctor\Mcp\McpArgumentValidator;
use kayedspace\Doctor\Rules\RuleCatalog;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DoctorExplainRuleTool extends Tool
{
    protected string $name = 'doctor_explain_rule';

    protected string $title = 'Laravel Doctor Explain Rule';

    protected string $description = 'Explain one Laravel Doctor rule.';

    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'ruleId' => $schema->string()
                ->description('The rule ID to explain.')
                ->required(),
        ];
    }

    public function handle(Request $request): mixed
    {
        $arguments = $request->all();
        McpArgumentValidator::assertSafe($arguments);

        $ruleId = (string) ($arguments['ruleId'] ?? '');
        $rule = $this->catalog->find($ruleId);
        if ($rule === null) {
            throw new \InvalidArgumentException("Unknown rule: {$ruleId}");
        }

        return Response::structured(['rule' => $rule]);
    }
}
