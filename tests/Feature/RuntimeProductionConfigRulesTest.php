<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;

test('production config rules are skipped when not booted', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    $request = new DoctorRequest($projectRoot);

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    expect($report->getSkippedRules())->toHaveKey(RuleId::ConfigAppKeyMissing->value);
});

test('production config rules run and report findings when booted and in production mode', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    app()->instance('env', 'production');
    Config::set('app.key', '');

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted')
        ->withRule(RuleId::ConfigAppKeyMissing);

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    expect($report->getFindings())->not->toBeEmpty();
    expect($report->getFindings()[0]->ruleId)->toBe(RuleId::ConfigAppKeyMissing->value);

    app()->instance('env', 'testing');
});
