<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\RuleRegistry;

class StaticTestRule implements DoctorRule
{
    public function id(): RuleId
    {
        return RuleId::DevelopmentDebugFunction;
    }

    public function name(): string
    {
        return $this->id()->ruleName();
    }

    public function description(): string
    {
        return $this->id()->description();
    }

    public function category(): RuleCategory
    {
        return $this->id()->category();
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Info;
    }

    public function defaultConfidence(): Confidence
    {
        return $this->id()->defaultConfidence();
    }

    public function remediation(): string
    {
        return $this->id()->remediation();
    }

    public function examples(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return $this->id()->capabilities();
    }

    public function isBeta(): bool
    {
        return false;
    }

    public function analyze(array $files = []): array
    {
        return [];
    }
}

class BootedTestRule implements DoctorRule
{
    public function id(): RuleId
    {
        return RuleId::SecurityGlobalModelUnguard;
    }

    public function name(): string
    {
        return $this->id()->ruleName();
    }

    public function description(): string
    {
        return $this->id()->description();
    }

    public function category(): RuleCategory
    {
        return $this->id()->category();
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Info;
    }

    public function defaultConfidence(): Confidence
    {
        return $this->id()->defaultConfidence();
    }

    public function remediation(): string
    {
        return $this->id()->remediation();
    }

    public function examples(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [RuleCapability::Booted];
    }

    public function isBeta(): bool
    {
        return false;
    }

    public function analyze(array $files = []): array
    {
        return [];
    }
}

beforeEach(function () {
    Config::set('doctor.rules', []);
    Config::set('doctor.severities', []);
});

test('it selects all rules by default', function () {
    $registry = new RuleRegistry([
        new StaticTestRule,
        new BootedTestRule,
    ]);

    $request = new DoctorRequest(__DIR__);
    $selection = $registry->select($request);

    // Default boot policy is static, so booted rule should be skipped
    expect($selection->getEligibleRules())->toHaveCount(1)
        ->and($selection->getEligibleRules()[0]->id())->toBe(RuleId::DevelopmentDebugFunction)
        ->and($selection->getSkippedRules())->toHaveKey(RuleId::SecurityGlobalModelUnguard->value);
});

test('it rejects unknown rules', function () {
    $registry = new RuleRegistry([new StaticTestRule]);
    $request = (new DoctorRequest(__DIR__))->withRule(['unknown.rule']);

    expect(fn () => $registry->select($request))
        ->toThrow(InvalidArgumentException::class, 'Unknown rule: unknown.rule');
});

test('configured rule wildcards disable matching rules', function () {
    Config::set('doctor.rules', ['security.*' => false]);

    $selection = RuleRegistry::default()->select(new DoctorRequest(__DIR__));
    $selectedRuleIds = array_merge(
        $selection->getDefaultRules(),
        array_map(fn (DoctorRule $rule): string => $rule->id()->value, $selection->getEligibleRules()),
        array_keys($selection->getSkippedRules())
    );

    expect(array_values(array_filter(
        $selectedRuleIds,
        fn (string $ruleId): bool => str_starts_with($ruleId, 'security.')
    )))->toBeEmpty();
});

test('configured exact rule disables still work', function () {
    Config::set('doctor.rules', [RuleId::DevelopmentDebugFunction->value => false]);

    $registry = new RuleRegistry([new StaticTestRule]);
    $selection = $registry->select(new DoctorRequest(__DIR__));

    expect($selection->getEligibleRules())->toBeEmpty();
});

test('configured wildcard severities apply to matching rules', function () {
    Config::set('doctor.severities', ['security.*' => 'error']);

    $selection = RuleRegistry::default()->select(new DoctorRequest(__DIR__));
    $rules = array_values(array_filter(
        $selection->getEligibleRules(),
        fn (DoctorRule $rule): bool => $rule->id() === RuleId::SecurityCsrfExceptWildcard
    ));

    expect($rules)->toHaveCount(1);
    expect($rules[0]->effectiveSeverity())->toBe(Severity::Error);
});

test('configured unknown rule ids still fail exactly', function () {
    Config::set('doctor.rules', ['unknown.rule' => false]);

    expect(fn () => RuleRegistry::default()->select(new DoctorRequest(__DIR__)))
        ->toThrow(InvalidArgumentException::class, 'Unknown rule: unknown.rule');
});

test('configured rule patterns must match at least one rule', function () {
    Config::set('doctor.rules', ['unknown.*' => false]);

    expect(fn () => RuleRegistry::default()->select(new DoctorRequest(__DIR__)))
        ->toThrow(InvalidArgumentException::class, 'No rules match pattern: unknown.*');
});

test('it rejects unknown packs', function () {
    $registry = new RuleRegistry([new StaticTestRule]);
    $request = (new DoctorRequest(__DIR__))->withPack(['unknown.pack']);

    expect(fn () => $registry->select($request))
        ->toThrow(InvalidArgumentException::class, 'Unknown rule pack: unknown.pack');
});

test('it allows booted rules when boot policy is booted', function () {
    $registry = new RuleRegistry([
        new StaticTestRule,
        new BootedTestRule,
    ]);

    $request = (new DoctorRequest(__DIR__))->withBootPolicy('booted');
    $selection = $registry->select($request);

    expect($selection->getEligibleRules())->toHaveCount(2)
        ->and($selection->getSkippedRules())->toBeEmpty();
});

test('default registry exposes canonical rule ids only once', function () {
    $ids = array_map(
        fn (DoctorRule $rule): string => $rule->id()->value,
        RuleRegistry::default()->getRules()
    );

    expect($ids)->not->toContain('security.weak-hash')
        ->and($ids)->toContain(RuleId::SecurityWeakHashAlgorithm->value)
        ->and(array_unique($ids))->toHaveCount(count($ids));
});

test('it supports selecting the health pack', function () {
    $request = (new DoctorRequest(__DIR__))->withPack('health');
    $registry = new RuleRegistry([
        new class implements DoctorRule
        {
            public function id(): RuleId
            {
                return RuleId::HealthDatabaseUnreachable;
            }

            public function name(): string
            {
                return 'DB';
            }

            public function description(): string
            {
                return 'desc';
            }

            public function category(): RuleCategory
            {
                return RuleCategory::Health;
            }

            public function defaultSeverity(): Severity
            {
                return Severity::Error;
            }

            public function defaultConfidence(): Confidence
            {
                return $this->id()->defaultConfidence();
            }

            public function remediation(): string
            {
                return $this->id()->remediation();
            }

            public function examples(): array
            {
                return [];
            }

            public function capabilities(): array
            {
                return [RuleCapability::Booted];
            }

            public function isBeta(): bool
            {
                return false;
            }

            public function analyze(array $files = []): array
            {
                return [];
            }
        },
    ]);
    $selection = $registry->select($request);

    expect($selection->getSkippedRules())->toHaveKey(RuleId::HealthDatabaseUnreachable->value);
});

test('dependency rules are selected only when dependency audit is enabled', function () {
    $defaultSelection = RuleRegistry::default()->select(new DoctorRequest(__DIR__));
    $auditSelection = RuleRegistry::default()->select((new DoctorRequest(__DIR__))->withAuditDependencies());

    expect(array_map(fn (DoctorRule $rule): string => $rule->id()->value, $defaultSelection->getEligibleRules()))
        ->not->toContain(RuleId::DependencyKnownVulnerability->value)
        ->and($defaultSelection->getSkippedRules())->not->toHaveKey(RuleId::DependencyKnownVulnerability->value)
        ->and(array_map(fn (DoctorRule $rule): string => $rule->id()->value, $auditSelection->getEligibleRules()))
        ->toContain(RuleId::DependencyKnownVulnerability->value);
});
