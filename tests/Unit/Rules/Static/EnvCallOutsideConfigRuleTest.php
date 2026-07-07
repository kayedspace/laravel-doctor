<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Rules\Static\EnvCallOutsideConfigRule;
use kayedspace\Doctor\Support\PhpSourceParser;
use kayedspace\Doctor\Tests\Support\SourceFileFixture;

test('it exposes enum-backed metadata', function () {
    $rule = new EnvCallOutsideConfigRule;

    expect($rule->id())->toBe(RuleId::FrameworkEnvOutsideConfig)
        ->and($rule->category())->toBe(RuleCategory::Framework)
        ->and($rule->capabilities())->toBe([RuleCapability::Static])
        ->and($rule->isBeta())->toBeFalse();
});

test('it detects env calls outside config files', function () {
    $rule = new EnvCallOutsideConfigRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Http/Controllers/EnvOutsideConfigController.php'),
    ]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->ruleId)->toBe(RuleId::FrameworkEnvOutsideConfig->value);
    expect($findings[0]->severity)->toBe(Severity::Error);
    expect($findings[0]->confidence)->toBe(Confidence::High);
    expect($findings[0]->file)->toBe('app/Http/Controllers/EnvOutsideConfigController.php');
    expect($findings[0]->evidence)->toBe("env('API_TOKEN')");
    expect($findings[0]->remediation)->toContain("config('key')");
});

test('it ignores env calls inside config files', function () {
    $rule = new EnvCallOutsideConfigRule;

    expect($rule->analyze([
        SourceFileFixture::forStaticRules('config/static_rules.php'),
    ]))->toBeEmpty();
});

test('it reports exact env call lines and one finding per occurrence', function () {
    $rule = new EnvCallOutsideConfigRule;
    $findings = $rule->analyze([
        SourceFileFixture::forStaticRules('app/Http/Controllers/EnvLocationController.php'),
    ]);

    expect($findings)->toHaveCount(2);
    expect(array_map(fn ($finding) => $finding->line, $findings))->toBe([12, 13]);
    expect($findings[0]->id)->toStartWith(RuleId::FrameworkEnvOutsideConfig->value.'.');
});

test('it flags env calls in test files as low confidence warnings', function () {
    $rule = new EnvCallOutsideConfigRule;
    $path = realpath(__DIR__.'/../../../Fixtures/projects/static-rules/app/Http/Controllers/EnvOutsideConfigController.php');
    $parser = new PhpSourceParser;

    $file = new SourceFile(
        path: 'tests/Feature/EnvOutsideConfigController.php',
        realPath: $path,
        contents: file_get_contents($path),
        syntaxTree: $parser->parse($path)
    );

    $findings = $rule->analyze([$file]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->severity)->toBe(Severity::Warning);
    expect($findings[0]->confidence)->toBe(Confidence::Low);
});
