<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Reports;

readonly class SavedReportMetadata
{
    /**
     * @param  array<string, int>  $summary
     */
    public function __construct(
        public string $reportId,
        public string $createdAt,
        public string $status,
        public array $summary,
        public string $scopeLabel,
        public string $schemaVersion,
        public ?string $path = null,
        public bool $valid = true,
        public ?string $error = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'reportId' => $this->reportId,
            'createdAt' => $this->createdAt,
            'status' => $this->status,
            'summary' => $this->summary,
            'scopeLabel' => $this->scopeLabel,
            'schemaVersion' => $this->schemaVersion,
            'path' => $this->path,
            'valid' => $this->valid,
            'error' => $this->error,
        ];
    }
}
