<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

class SarifReportFormatter implements ReportFormatter
{
    public function format(DoctorReport $report, OutputPolicy $policy): string
    {
        $rules = $this->rulesFor($report);
        $ruleIndexes = array_flip(array_column($rules, 'id'));

        $document = [
            'version' => '2.1.0',
            '$schema' => 'https://docs.oasis-open.org/sarif/sarif/v2.1.0/os/schemas/sarif-schema-2.1.0.json',
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => 'Laravel Doctor',
                            'rules' => $rules,
                        ],
                    ],
                    'results' => array_map(
                        fn (DoctorFinding $finding): array => $this->resultFor($finding, $ruleIndexes),
                        $report->getFindings()
                    ),
                ],
            ],
        ];

        return (string) json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rulesFor(DoctorReport $report): array
    {
        $rules = [];

        foreach ($report->getFindings() as $finding) {
            if (isset($rules[$finding->ruleId])) {
                continue;
            }

            $ruleId = RuleId::tryFrom($finding->ruleId);
            $rules[$finding->ruleId] = [
                'id' => $finding->ruleId,
                'name' => $ruleId?->ruleName() ?? $finding->title,
                'shortDescription' => ['text' => $finding->title],
                'fullDescription' => ['text' => $ruleId?->description() ?? $finding->message],
                'help' => ['text' => $finding->remediation ?? $ruleId?->remediation() ?? 'Review the reported finding.'],
                'properties' => [
                    'category' => $ruleId?->category()->value,
                    'defaultSeverity' => $ruleId?->defaultSeverity()->value,
                    'defaultConfidence' => $ruleId?->defaultConfidence()->value,
                    'beta' => $ruleId?->isBeta() ?? false,
                    'tags' => $ruleId?->tags() ?? $finding->tags,
                    'capabilities' => array_map(
                        fn ($capability): string => $capability->value,
                        $ruleId?->capabilities() ?? []
                    ),
                ],
            ];
        }

        return array_values($rules);
    }

    /**
     * @param  array<string, int>  $ruleIndexes
     * @return array<string, mixed>
     */
    private function resultFor(DoctorFinding $finding, array $ruleIndexes): array
    {
        $result = [
            'ruleId' => $finding->ruleId,
            'ruleIndex' => $ruleIndexes[$finding->ruleId] ?? 0,
            'level' => $this->levelFor($finding->severity),
            'message' => [
                'text' => $finding->message.' Evidence: '.$finding->toArray()['evidence'],
            ],
            'rank' => $this->rankFor($finding->confidence),
            'partialFingerprints' => [
                'doctorFingerprint' => $finding->id,
            ],
            'properties' => [
                'confidence' => $finding->confidence->value,
                'remediation' => $finding->remediation,
                'tags' => $finding->tags,
                'evidence' => $finding->toArray()['evidence'],
            ],
        ];

        if ($finding->file !== null) {
            $physicalLocation = [
                'artifactLocation' => [
                    'uri' => $finding->file,
                    'uriBaseId' => '%SRCROOT%',
                ],
            ];

            if ($finding->line !== null) {
                $physicalLocation['region'] = ['startLine' => $finding->line];
            }

            $result['locations'] = [
                ['physicalLocation' => $physicalLocation],
            ];
        }

        return $result;
    }

    private function levelFor(Severity $severity): string
    {
        return match ($severity) {
            Severity::Critical, Severity::Error => 'error',
            Severity::Warning => 'warning',
            Severity::Info => 'note',
        };
    }

    private function rankFor(Confidence $confidence): float
    {
        return match ($confidence) {
            Confidence::High => 100.0,
            Confidence::Medium => 66.0,
            Confidence::Low => 33.0,
        };
    }
}
