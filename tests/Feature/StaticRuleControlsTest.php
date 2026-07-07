<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;

test('inline suppression drops matching and bare ignored findings before aggregation', function () {
    $request = (new DoctorRequest(__DIR__.'/../Fixtures/projects/static-rules'))
        ->withRule([RuleId::DevelopmentDebugFunction]);

    $report = app(DoctorScanAction::class)->execute($request);
    $findings = array_values(array_filter(
        $report->getFindings(),
        fn ($finding) => $finding->file === 'app/Http/Controllers/InlineSuppressionController.php'
    ));

    expect($findings)->toHaveCount(1);
    expect($findings[0]->evidence)->toBe('var_dump(...)');
});

test('rule config disables rules and overrides severities', function () {
    Config::set('doctor.rules', [RuleId::DevelopmentDebugFunction->value => false]);
    Config::set('doctor.severities', [RuleId::SecurityRawSqlInterpolation->value => 'error']);

    $request = new DoctorRequest(__DIR__.'/../Fixtures/projects/static-rules');
    $report = app(DoctorScanAction::class)->execute($request);

    foreach ($report->getFindings() as $finding) {
        expect($finding->ruleId)->not->toBe(RuleId::DevelopmentDebugFunction->value);
    }

    $rawSql = collect($report->getFindings())->first(fn ($finding) => $finding->ruleId === RuleId::SecurityRawSqlInterpolation->value);
    expect($rawSql->severity)->toBe(Severity::Error);
});

test('unknown configured rule id fails like unknown selected rule', function () {
    Config::set('doctor.rules', ['unknown.rule' => false]);
    Config::set('doctor.severities', []);

    $request = new DoctorRequest(__DIR__.'/../Fixtures/projects/static-rules');
    $report = app(DoctorScanAction::class)->execute($request);

    expect(array_map(fn ($error) => $error->message, $report->getErrors()))->toContain('Unknown rule: unknown.rule');
    expect($report->getStatus())->toBe('failed');
});
