<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

class JsonReportFormatter implements ReportFormatter
{
    public function format(DoctorReport $report, OutputPolicy $policy): string
    {
        return (string) json_encode($report->toArray(), JSON_PRETTY_PRINT);
    }
}
