<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\Static\DebugFunctionRule;
use kayedspace\Doctor\Tests\Support\SourceFileFixture;

test('it exposes enum-backed metadata', function () {
    $rule = new DebugFunctionRule;

    expect($rule->id())->toBe(RuleId::DevelopmentDebugFunction)
        ->and($rule->category())->toBe(RuleCategory::Development)
        ->and($rule->defaultSeverity())->toBe(Severity::Warning)
        ->and($rule->capabilities())->toBe([RuleCapability::Static])
        ->and($rule->isBeta())->toBeFalse();
});

test('it detects debug functions', function () {
    $rule = new DebugFunctionRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Http/Controllers/DebugFunctionController.php'),
    ]);

    expect($findings)->toHaveCount(4);
    expect($findings[0]->ruleId)->toBe(RuleId::DevelopmentDebugFunction->value);
    expect($findings[0]->severity)->toBe(Severity::Warning);
    expect($findings[0]->confidence)->toBe(Confidence::High);
    expect($findings[0]->file)->toBe('app/Http/Controllers/DebugFunctionController.php');
    expect($findings[0]->evidence)->toBe('dd(...)');
    expect($findings[0]->remediation)->toContain('structured logging');
});

test('it ignores non-debug output and text mentions', function () {
    $rule = new DebugFunctionRule;

    expect($rule->analyze([
        SourceFileFixture::forStaticRules('app/Http/Controllers/SafeOutputController.php'),
    ]))->toBeEmpty();
});

test('it reports exact debug call lines and one finding per occurrence', function () {
    $rule = new DebugFunctionRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Http/Controllers/DebugFunctionLocationsController.php'),
    ]);

    expect($findings)->toHaveCount(4);
    expect(array_map(fn ($finding) => $finding->line, $findings))->toBe([11, 13, 15, 17]);
    expect($findings[0]->id)->toStartWith(RuleId::DevelopmentDebugFunction->value.'.');
});
