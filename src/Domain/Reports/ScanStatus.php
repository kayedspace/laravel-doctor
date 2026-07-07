<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Reports;

enum ScanStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
}
