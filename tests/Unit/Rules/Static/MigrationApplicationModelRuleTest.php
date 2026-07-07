<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\Static\MigrationApplicationModelRule;
use kayedspace\Doctor\Tests\Support\SourceFileFixture;

test('it exposes enum-backed metadata', function () {
    $rule = new MigrationApplicationModelRule;

    expect($rule->id())->toBe(RuleId::MigrationApplicationModel)
        ->and($rule->category())->toBe(RuleCategory::Migration)
        ->and($rule->defaultSeverity())->toBe(Severity::Warning)
        ->and($rule->capabilities())->toBe([RuleCapability::Static])
        ->and($rule->isBeta())->toBeFalse();
});

test('it detects application model references in migrations', function () {
    $rule = new MigrationApplicationModelRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('database/migrations/2026_01_01_000001_positive_model_reference.php'),
    ]);

    expect($findings)->toHaveCount(4);
    expect($findings[0]->ruleId)->toBe(RuleId::MigrationApplicationModel->value);
    expect($findings[0]->severity)->toBe(Severity::Warning);
    expect($findings[0]->confidence)->toBe(Confidence::Medium);
    expect($findings[0]->file)->toBe('database/migrations/2026_01_01_000001_positive_model_reference.php');
    expect(array_map(fn ($finding) => $finding->evidence, $findings))->toContain('App\\Models\\User');
    expect($findings[0]->remediation)->toContain('schema operations');
});

test('it ignores non-model application namespace references in migrations', function () {
    $rule = new MigrationApplicationModelRule;

    expect($rule->analyze([
        SourceFileFixture::forStaticRules('database/migrations/2026_01_01_000002_negative_non_model_reference.php'),
    ]))->toBeEmpty();
});

test('it ignores application model references outside migration files', function () {
    $rule = new MigrationApplicationModelRule;

    expect($rule->analyze([
        SourceFileFixture::forStaticRules('app/Providers/UnguardServiceProvider.php'),
    ]))->toBeEmpty();
});

test('it reports exact model reference lines and one finding per occurrence', function () {
    $rule = new MigrationApplicationModelRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('database/migrations/2026_01_01_000003_model_reference_locations.php'),
    ]);

    expect($findings)->toHaveCount(4);
    expect(array_map(fn ($finding) => $finding->line, $findings))->toBe([5, 6, 13, 15]);
    expect($findings[0]->id)->toStartWith(RuleId::MigrationApplicationModel->value.'.');
});
