<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;

test('runtime operational rules are skipped when not booted', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    $request = new DoctorRequest($projectRoot);

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    // Operational rules should be skipped
    expect($report->getSkippedRules())->toHaveKey(RuleId::QueueTimeoutRetryAfter->value);
});

test('runtime operational rules run and report findings when booted is enabled', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    // Set a risky queue config
    Config::set('queue.connections.risky', [
        'driver' => 'redis',
        'timeout' => 60,
        'retry_after' => 60,
    ]);

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted')
        ->withRule(RuleId::QueueTimeoutRetryAfter);

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    expect($report->getFindings())->not->toBeEmpty();
    expect($report->getFindings()[0]->ruleId)->toBe(RuleId::QueueTimeoutRetryAfter->value);
});
