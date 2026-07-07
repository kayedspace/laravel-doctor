<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Support\Reports\ReportSerializer;

test('saved report serializer redacts absolute project root and emits schema metadata', function () {
    $root = realpath(__DIR__.'/../../../Fixtures/projects/safe-project');
    $report = (new DoctorReport(new DoctorRequest($root)))->complete();

    $data = (new ReportSerializer)->serialize($report, 'report_test', new DateTimeImmutable('2026-07-03T12:00:00+00:00'));

    expect($data['schemaVersion'])->toBe('1')
        ->and($data['reportId'])->toBe('report_test')
        ->and($data['project']['name'])->toBe('safe-project')
        ->and(json_encode($data))->not->toContain((string) $root)
        ->and($data['request'])->not->toHaveKey('projectRoot');
});
