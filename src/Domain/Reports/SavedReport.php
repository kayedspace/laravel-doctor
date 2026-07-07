<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Reports;

readonly class SavedReport
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public SavedReportMetadata $metadata,
        public array $data,
    ) {}
}
