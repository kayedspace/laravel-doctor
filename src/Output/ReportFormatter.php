<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

interface ReportFormatter
{
    public function format(DoctorReport $report, OutputPolicy $policy): string;
}
