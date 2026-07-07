<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\Booted\AbstractBootedRule;
use kayedspace\Doctor\Support\Fingerprints\FindingFingerprint;

class ConcreteBootedRuleStub extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::ConfigAppKeyMissing;

    public function analyze(array $files = []): array
    {
        return [
            $this->makeFinding(
                message: 'Test message',
                evidence: 'Test evidence'
            )->title('Test Title'),
        ];
    }
}

test('abstract booted rule creates finding with correct fingerprint and handles severity override', function () {
    $rule = new ConcreteBootedRuleStub;

    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1);

    $finding = $findings[0];
    expect($finding->ruleId)->toBe(RuleId::ConfigAppKeyMissing->value)
        ->and($finding->severity)->toBe(RuleId::ConfigAppKeyMissing->defaultSeverity())
        ->and($finding->evidence)->toBe('Test evidence')
        ->and($finding->file)->toBeNull()
        ->and($finding->line)->toBeNull()
        ->and($finding->id)->toBe(FindingFingerprint::make(RuleId::ConfigAppKeyMissing->value, null, 'Test evidence', disambiguator: 0));

    // Severity override
    $rule->withSeverityOverride(Severity::Critical);
    $findings = $rule->analyze();
    expect($findings[0]->severity)->toBe(Severity::Critical);
});
