<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

class MarkdownReportFormatter implements ReportFormatter
{
    public function format(DoctorReport $report, OutputPolicy $policy): string
    {
        $output = new DoctorOutput($report->getFindings());

        return $output->toMarkdown();
    }

    /**
     * @param  array<string, mixed>  $finding
     */
    public static function formatFindingArray(array $finding): string
    {
        return DoctorFinding::fromArray($finding)->toMarkdown();
    }
}
