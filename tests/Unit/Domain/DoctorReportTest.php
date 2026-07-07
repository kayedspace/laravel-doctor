<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;

test('it is created in the created status with a start timestamp and optional request', function () {
    $request = new DoctorRequest(__DIR__);
    $report = new DoctorReport($request);

    expect($report->getRequest())->toBe($request)
        ->and($report->getStatus())->toBe('created')
        ->and($report->getStartedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($report->getCompletedAt())->toBeNull()
        ->and($report->getFindings())->toBeEmpty()
        ->and($report->getSummary()->toArray())->toBe([
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'critical' => 0,
            'skipped' => 0,
            'errors' => 0,
        ]);
});

test('it supports transitions to completed status', function () {
    $report = new DoctorReport(new DoctorRequest(__DIR__));
    $report->complete();

    expect($report->getStatus())->toBe('completed')
        ->and($report->getCompletedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('it supports transitions to failed status', function () {
    $report = new DoctorReport(new DoctorRequest(__DIR__));
    $report->fail();

    expect($report->getStatus())->toBe('failed')
        ->and($report->getCompletedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('it derives summary counts from findings by severity', function () {
    $report = new DoctorReport(new DoctorRequest(__DIR__));

    $finding1 = new DoctorFinding(
        id: 'f1',
        ruleId: 'rule1',
        title: 'Info title',
        message: 'Info finding',
        severity: Severity::Info,
        confidence: Confidence::Low,
        evidence: 'evidence1',
        remediation: 'remedy1'
    );

    $finding2 = new DoctorFinding(
        id: 'f2',
        ruleId: 'rule2',
        title: 'Error title',
        message: 'Error finding',
        severity: Severity::Error,
        confidence: Confidence::High,
        evidence: 'evidence2',
        remediation: 'remedy2'
    );

    $finding3 = new DoctorFinding(
        id: 'f3',
        ruleId: 'rule3',
        title: 'Critical title',
        message: 'Critical finding',
        severity: Severity::Critical,
        confidence: Confidence::Medium,
        evidence: 'evidence3',
        remediation: 'remedy3'
    );

    $report->addFinding($finding1);
    $report->addFinding($finding2);
    $report->addFinding($finding3);

    expect($report->getFindings())->toHaveCount(3)
        ->and($report->getSummary()->toArray())->toBe([
            'info' => 1,
            'warning' => 0,
            'error' => 1,
            'critical' => 1,
            'skipped' => 0,
            'errors' => 0,
        ]);
});

test('it holds a resolved scan plan', function () {
    $request = new DoctorRequest(__DIR__);
    $report = new DoctorReport($request);

    $plan = new ResolvedScanPlan(
        projectRoot: __DIR__,
        includedPaths: [],
        excludedPaths: [],
        selectedRules: [],
        skippedRules: [],
        availableCapabilities: [],
        bootPolicy: 'static'
    );

    $report->setPlan($plan);
    expect($report->getPlan())->toBe($plan);
});

test('it holds skipped rules and scan errors', function () {
    $report = new DoctorReport(new DoctorRequest(__DIR__));

    $report->addSkippedRule('rule1', 'Requires booted state');
    $report->addError('File parser failed at app/Models/User.php: syntax error');

    expect($report->getSkippedRules())->toBe(['rule1' => 'Requires booted state'])
        ->and($report->getErrors())->toHaveCount(1)
        ->and((string) $report->getErrors()[0])->toBe('File parser failed at app/Models/User.php: syntax error');
});

test('it exports to array representation', function () {
    $request = new DoctorRequest(__DIR__);
    $report = new DoctorReport($request);

    $plan = new ResolvedScanPlan(
        projectRoot: __DIR__,
        includedPaths: ['app'],
        excludedPaths: ['vendor'],
        selectedRules: [],
        skippedRules: [],
        availableCapabilities: ['static'],
        bootPolicy: 'static'
    );
    $report->setPlan($plan);
    $report->addSkippedRule('rule1', 'reason1');
    $report->addError('error1');
    $report->complete();

    $export = $report->toArray();

    expect($export['status'])->toBe('completed')
        ->and($export['skippedRules'])->toBe(['rule1' => 'reason1'])
        ->and($export['errors'])->toBe([['message' => 'error1', 'file' => null, 'line' => null]])
        ->and($export['request'])->toHaveKey('outputPolicy')
        ->and($export['plan']['includedPaths'])->toBe(['app'])
        ->and($export['plan']['excludedPaths'])->toBe(['vendor'])
        ->and($export['summary'])->toHaveKey('info');
});
