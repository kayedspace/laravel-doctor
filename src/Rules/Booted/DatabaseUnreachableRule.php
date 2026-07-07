<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\HealthSourceResolver;

class DatabaseUnreachableRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthDatabaseUnreachable;

    public function analyze(array $files = []): array
    {
        if (! HealthSourceResolver::isDatabaseHealthy()) {
            return [
                $this->makeFinding(
                    message: 'Database connection is unreachable.',
                    evidence: 'unreachable'
                ),
            ];
        }

        return [];
    }
}
