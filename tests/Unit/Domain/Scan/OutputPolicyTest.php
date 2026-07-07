<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

test('output policy holds format and thresholds', function () {
    $policy = new OutputPolicy('json', Severity::Error, Confidence::High, true, 'doctor-baseline.json');

    expect($policy->getFormat())->toBe('json');
    expect($policy->getFailOnSeverity())->toBe(Severity::Error);
    expect($policy->getFailOnConfidence())->toBe(Confidence::High);
    expect($policy->shouldFailOnNew())->toBeTrue();
    expect($policy->getBaselinePath())->toBe('doctor-baseline.json');
    expect($policy->toArray())->toBe([
        'format' => 'json',
        'failOnSeverity' => 'error',
        'failOnConfidence' => 'high',
        'failOnNew' => true,
        'baselinePath' => 'doctor-baseline.json',
    ]);
});

test('output policy validates format and fail on new baseline requirement', function () {
    expect(fn () => new OutputPolicy('xml'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported output format');

    expect(fn () => new OutputPolicy('json', failOnNew: true))
        ->toThrow(InvalidArgumentException::class, 'Baseline required when fail-on-new is enabled');
});

test('output policy accepts ai output formats and still rejects unknown formats', function () {
    expect((new OutputPolicy('markdown'))->getFormat())->toBe('markdown')
        ->and((new OutputPolicy('compact-json'))->getFormat())->toBe('compact-json')
        ->and(fn () => new OutputPolicy('compact'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported output format');
});

test('output policy shouldFail checks severity thresholds', function () {
    $policy = new OutputPolicy('console', Severity::Error);

    $report = new DoctorReport(new DoctorRequest(__DIR__));
    expect($policy->shouldFail($report))->toBeFalse();

    $findingWarning = new DoctorFinding('id1', 'r1', 't1', 'm1', Severity::Warning, Confidence::High, 'ev', remediation: 'rem');
    $report->addFinding($findingWarning);
    expect($policy->shouldFail($report))->toBeFalse();

    $findingError = new DoctorFinding('id2', 'r2', 't2', 'm2', Severity::Error, Confidence::High, 'ev', remediation: 'rem');
    $report->addFinding($findingError);
    expect($policy->shouldFail($report))->toBeTrue();
});

test('output policy shouldFail checks confidence thresholds', function () {
    $policy = new OutputPolicy('console', null, Confidence::High);

    $report = new DoctorReport(new DoctorRequest(__DIR__));
    expect($policy->shouldFail($report))->toBeFalse();

    $findingMedium = new DoctorFinding('id1', 'r1', 't1', 'm1', Severity::Error, Confidence::Medium, 'ev', remediation: 'rem');
    $report->addFinding($findingMedium);
    expect($policy->shouldFail($report))->toBeFalse();

    $findingHigh = new DoctorFinding('id2', 'r2', 't2', 'm2', Severity::Error, Confidence::High, 'ev', remediation: 'rem');
    $report->addFinding($findingHigh);
    expect($policy->shouldFail($report))->toBeTrue();
});
