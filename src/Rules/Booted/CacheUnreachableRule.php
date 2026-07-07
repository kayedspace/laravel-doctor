<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\HealthSourceResolver;

class CacheUnreachableRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthCacheUnreachable;

    public function analyze(array $files = []): array
    {
        if (! HealthSourceResolver::isCacheHealthy()) {
            return [
                $this->makeFinding(
                    message: 'Cache service is unreachable.',
                    evidence: 'unreachable'
                ),
            ];
        }

        return [];
    }
}
