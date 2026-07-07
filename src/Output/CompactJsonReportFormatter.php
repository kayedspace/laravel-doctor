<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Rules\RuleCatalog;

class CompactJsonReportFormatter implements ReportFormatter
{
    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    public function format(DoctorReport $report, OutputPolicy $policy): string
    {
        $output = new DoctorOutput($report->getFindings());

        return (string) json_encode($output->toCompactArray($this->catalog), JSON_UNESCAPED_SLASHES);
    }
}
