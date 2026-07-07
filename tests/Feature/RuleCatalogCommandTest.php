<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use kayedspace\Doctor\Domain\Enums\RuleId;

test('doctor rules lists registered rule metadata', function () {
    $exitCode = Artisan::call('doctor:rules');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain(RuleId::FrameworkEnvOutsideConfig->value)
        ->and($output)->toContain(RuleId::FrameworkEnvOutsideConfig->ruleName())
        ->and($output)->toContain('framework')
        ->and($output)->toContain('error')
        ->and($output)->toContain('high')
        ->and($output)->toContain(RuleId::EloquentAllThenFilter->value)
        ->and($output)->toContain('yes');
});

test('doctor explain shows known rule details and examples when present', function () {
    $exitCode = Artisan::call('doctor:explain', [
        'rule-id' => RuleId::SecurityRawSqlInterpolation->value,
    ]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('ID: '.RuleId::SecurityRawSqlInterpolation->value)
        ->and($output)->toContain('Description: '.RuleId::SecurityRawSqlInterpolation->description())
        ->and($output)->toContain('Remediation: '.RuleId::SecurityRawSqlInterpolation->remediation())
        ->and($output)->toContain('Examples:')
        ->and($output)->toContain('DB::raw');
});

test('doctor explain omits examples for rules without examples', function () {
    $exitCode = Artisan::call('doctor:explain', [
        'rule-id' => RuleId::FrameworkEnvOutsideConfig->value,
    ]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('ID: '.RuleId::FrameworkEnvOutsideConfig->value)
        ->and($output)->not->toContain('Examples:');
});

test('doctor explain fails clearly for unknown rule', function () {
    $exitCode = Artisan::call('doctor:explain', [
        'rule-id' => 'missing.rule',
    ]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('Unknown rule: missing.rule');
});
