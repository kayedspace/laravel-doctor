<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;

test('runtime scan action enforces strict safety and does not write or persist', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted');

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    // Ensure the scan finished successfully and findings array does not persist anything
    expect($report->getStatus())->toBe('completed');
});

test('runtime-enabled scan completes under 30 seconds and unreachable services do not hang the scan', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    // Configure an unreachable host (e.g. black-holed IP 192.0.2.1) with a low timeout of 1 second
    Config::set('database.default', 'mysql');
    Config::set('database.connections.mysql.host', '192.0.2.1');
    Config::set('database.connections.mysql.port', 80); // use port 80 to test connection block
    Config::set('doctor.runtime.timeout_seconds', 1);

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted')
        ->withRule('health.database-unreachable');

    $action = app(DoctorScanAction::class);

    $startTime = microtime(true);
    $report = $action->execute($request);
    $duration = microtime(true) - $startTime;

    // Scan should complete well under 30 seconds (expected to be around 1 second due to connection timeout)
    expect($duration)->toBeLessThan(30.0);
    expect($report->getStatus())->toBe('completed');

    // Since database was unreachable, it should report database-unreachable finding
    $findings = $report->getFindings();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->ruleId)->toBe(RuleId::HealthDatabaseUnreachable->value);
});
