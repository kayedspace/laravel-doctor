<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

class ConsoleReportFormatter implements ReportFormatter
{
    public function format(DoctorReport $report, OutputPolicy $policy): string
    {
        $lines = [
            'Laravel Live Doctor analysis engine starting...',
            'Scanning project...',
        ];

        if ($report->hasFindings()) {
            $lines[] = 'Scan completed with findings:';
            foreach ($report->getFindings() as $finding) {
                $lines[] = '--------------------------------------------------';
                $lines[] = 'Rule ID:     '.$finding->ruleId;
                $lines[] = 'Title:       '.$finding->title;
                $lines[] = 'Severity:    '.strtoupper($finding->severity->value);
                $lines[] = 'Confidence:  '.strtoupper($finding->confidence->value);
                $lines[] = 'File:        '.($finding->file ? $finding->file.($finding->line ? ':'.$finding->line : '') : 'N/A');
                $lines[] = 'Message:     '.$finding->message;
                $lines[] = 'Evidence:    '.$finding->toArray()['evidence'];
                $lines[] = 'Remediation: '.$finding->remediation;
            }
            $lines[] = '--------------------------------------------------';
        } else {
            $lines[] = 'Scan completed. No findings.';
        }

        if ($report->getErrors() !== []) {
            $lines[] = 'Scan Errors encountered during analysis:';
            foreach ($report->getErrors() as $error) {
                $lines[] = '  - '.$error;
            }
        }

        if ($report->getSkippedRules() !== []) {
            $lines[] = 'Skipped Rules:';
            foreach ($report->getSkippedRules() as $ruleId => $reason) {
                $lines[] = "  - {$ruleId}: {$reason}";
            }
        }

        $summary = $report->getSummary();
        $lines[] = "Summary counts: Info: {$summary->info}, Warning: {$summary->warning}, Error: {$summary->error}, Critical: {$summary->critical}";

        if ($policy->shouldFail($report)) {
            $lines[] = 'Scan failed exit policy threshold.';
        }

        return implode(PHP_EOL, $lines);
    }
}
