<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use kayedspace\Doctor\Rules\RuleCatalog;

class ExplainRuleCommand extends Command
{
    protected $signature = 'doctor:explain {rule-id : Rule identifier to explain}';

    protected $description = 'Explain a Laravel Doctor rule';

    public function handle(RuleCatalog $catalog): int
    {
        $ruleId = (string) $this->argument('rule-id');
        $rule = $catalog->find($ruleId);

        if ($rule !== null) {
            $this->line('ID: '.$rule['id']);
            $this->line('Name: '.$rule['name']);
            $this->line('Category: '.$rule['category']);
            $this->line('Default Severity: '.$rule['severity']);
            $this->line('Default Confidence: '.$rule['confidence']);
            $this->line('Beta: '.($rule['beta'] ? 'yes' : 'no'));
            $this->line('Capabilities: '.implode(', ', $rule['capabilities']));
            $this->line('Description: '.$rule['description']);
            $this->line('Remediation: '.$rule['remediation']);

            if ($rule['examples'] !== []) {
                $this->line('Examples:');
                foreach ($rule['examples'] as $example) {
                    $this->line('  - '.$example);
                }
            }

            return 0;
        }

        $this->error('Unknown rule: '.$ruleId);

        return 1;
    }
}
