<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Output\CompactJsonReportFormatter;
use kayedspace\Doctor\Output\JsonReportFormatter;

test('compact json formatter returns a minified array of compact findings', function () {
    $report = $this->doctorReportWithFindings();
    $formatter = app(CompactJsonReportFormatter::class);

    $json = $formatter->format($report, new OutputPolicy('compact-json'));
    $data = json_decode($json, true);

    expect($json)->not->toContain("\n")
        ->and($data)->toHaveKeys(['rules', 'findings'])
        ->and($data['findings'])->toHaveCount(4)
        ->and($data['findings'][0])->toHaveKeys(['rule', 'severity', 'location', 'message'])
        ->and(array_keys($data['findings'][0]))->toBe(['rule', 'severity', 'location', 'message']);
});

test('compact json is at least forty percent smaller while preserving finding order and severity', function () {
    $report = $this->doctorReportWithFindings();
    $compact = app(CompactJsonReportFormatter::class)->format($report, new OutputPolicy('compact-json'));
    $standard = (new JsonReportFormatter)->format($report, new OutputPolicy('json'));

    $compactData = json_decode($compact, true);

    expect(strlen($compact))->toBeLessThan((int) floor(strlen($standard) * 0.6))
        ->and(array_column($compactData['findings'], 'rule'))->toBe(array_map(
            static fn ($finding): string => $finding->ruleId,
            $report->getFindings()
        ))
        ->and(array_column($compactData['findings'], 'severity'))->toBe(array_map(
            static fn ($finding): string => $finding->severity->value,
            $report->getFindings()
        ))
        ->and($report->getStatus())->toBe('completed');
});
