<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Baseline\BaselineComparison;
use kayedspace\Doctor\Domain\Baseline\BaselineReport;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;

test('baseline comparison partitions known and new findings', function () {
    $known = new DoctorFinding('known-id', 'rule', 'Known', 'Known', Severity::Warning, Confidence::High, 'evidence', remediation: 'fix');
    $new = new DoctorFinding('new-id', 'rule', 'New', 'New', Severity::Warning, Confidence::High, 'evidence', remediation: 'fix');

    $comparison = BaselineComparison::compare([$known, $new], new BaselineReport('baseline.json', ['known-id']));

    expect($comparison->knownFindings)->toBe([$known])
        ->and($comparison->newFindings)->toBe([$new])
        ->and($comparison->hasNewFindings())->toBeTrue();
});
