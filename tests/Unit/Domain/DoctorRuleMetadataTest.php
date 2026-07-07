<?php

declare(strict_types=1);

use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;

class StubRule implements DoctorRule
{
    public bool $analyzed = false;

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
        return Severity::Warning;
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
        return $this->id()->examples();
    }

    public function capabilities(): array
    {
        return $this->id()->capabilities();
    }

    public function isBeta(): bool
    {
        return true;
    }

    public function analyze(array $files = []): array
    {
        $this->analyzed = true;

        return [];
    }
}

test('it reads rule metadata without executing analysis', function () {
    $rule = new StubRule;

    expect($rule->id())->toBe(RuleId::DevelopmentDebugFunction)
        ->and($rule->name())->toBe(RuleId::DevelopmentDebugFunction->ruleName())
        ->and($rule->description())->toBe(RuleId::DevelopmentDebugFunction->description())
        ->and($rule->category())->toBe(RuleCategory::Development)
        ->and($rule->defaultSeverity())->toBe(Severity::Warning)
        ->and($rule->defaultConfidence())->toBe(RuleId::DevelopmentDebugFunction->defaultConfidence())
        ->and($rule->remediation())->toBe(RuleId::DevelopmentDebugFunction->remediation())
        ->and($rule->examples())->toBe([])
        ->and($rule->capabilities())->toBe([RuleCapability::Static])
        ->and($rule->isBeta())->toBeTrue()
        ->and($rule->analyzed)->toBeFalse();

    // Ensure we didn't call analyze() to fetch metadata
});

test('rule ids expose catalog examples when available', function () {
    expect(RuleId::SecurityRawSqlInterpolation->examples())->not->toBeEmpty()
        ->and(RuleId::SecurityRawSqlInterpolation->examples()[0])->toContain('DB::raw')
        ->and(RuleId::FrameworkEnvOutsideConfig->examples())->toBe([]);
});

test('it has correct metadata for runtime rules', function () {
    // Assert RuleCategory::Health exists
    expect(RuleCategory::Health->value)->toBe('health');

    $runtimeRules = [
        'queue.timeout-retry-after' => [RuleCategory::Framework, false, [RuleCapability::Booted]],
        'queue.dispatch-before-commit' => [RuleCategory::Framework, true, [RuleCapability::Booted]],
        'queue.unique-lock-store' => [RuleCategory::Framework, true, [RuleCapability::Booted]],
        'scheduler.single-server-lock-store' => [RuleCategory::Framework, true, [RuleCapability::Booted]],
        'cache.flush-shared-store' => [RuleCategory::Framework, true, [RuleCapability::Booted]],
        'health.database-unreachable' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'health.cache-unreachable' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'health.disk-space-low' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'health.storage-not-writable' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'health.pending-migrations' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'health.maintenance-mode' => [RuleCategory::Health, false, [RuleCapability::Booted]],
        'config.app-key-missing' => [RuleCategory::Framework, false, [RuleCapability::Booted]],
        'config.unsafe-driver' => [RuleCategory::Framework, false, [RuleCapability::Booted]],
        'security.missing-security-headers' => [RuleCategory::Security, false, [RuleCapability::Booted]],
        'security.login-not-throttled' => [RuleCategory::Security, true, [RuleCapability::Booted]],
        'performance.runtime-n-plus-one' => [RuleCategory::Eloquent, true, [RuleCapability::Booted]],
    ];

    foreach ($runtimeRules as $value => $meta) {
        $case = RuleId::from($value);
        expect($case->category())->toBe($meta[0])
            ->and($case->isBeta())->toBe($meta[1])
            ->and($case->capabilities())->toBe($meta[2]);
    }
});
