<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\Static\GlobalModelUnguardRule;
use kayedspace\Doctor\Tests\Support\SourceFileFixture;

test('it exposes enum-backed metadata', function () {
    $rule = new GlobalModelUnguardRule;

    expect($rule->id())->toBe(RuleId::SecurityGlobalModelUnguard)
        ->and($rule->category())->toBe(RuleCategory::Security)
        ->and($rule->defaultSeverity())->toBe(Severity::Error)
        ->and($rule->capabilities())->toBe([RuleCapability::Static])
        ->and($rule->isBeta())->toBeFalse();
});

test('it detects global model unguard calls', function () {
    $rule = new GlobalModelUnguardRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Providers/UnguardServiceProvider.php'),
    ]);

    expect($findings)->toHaveCount(2);
    expect($findings[0]->ruleId)->toBe(RuleId::SecurityGlobalModelUnguard->value);
    expect($findings[0]->severity)->toBe(Severity::Error);
    expect($findings[0]->confidence)->toBe(Confidence::High);
    expect($findings[1]->confidence)->toBe(Confidence::Medium);
    expect($findings[0]->file)->toBe('app/Providers/UnguardServiceProvider.php');
    expect($findings[0]->evidence)->toBe('Illuminate\\Database\\Eloquent\\Model::unguard()');
    expect($findings[0]->remediation)->toContain('Model::unguarded');
});

test('it ignores guarded fillable scoped unguarded and reguard patterns', function () {
    $rule = new GlobalModelUnguardRule;

    expect($rule->analyze([
        SourceFileFixture::forStaticRules('app/Models/SafeGuardedModel.php'),
    ]))->toBeEmpty();
});

test('it reports exact unguard lines and one finding per occurrence', function () {
    $rule = new GlobalModelUnguardRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Providers/UnguardLocationsServiceProvider.php'),
    ]);

    expect($findings)->toHaveCount(2);
    expect(array_map(fn ($finding) => $finding->line, $findings))->toBe([14, 16]);
    expect($findings[0]->id)->toStartWith(RuleId::SecurityGlobalModelUnguard->value.'.');
});
