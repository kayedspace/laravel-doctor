<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Reports;

readonly class ScanStatusSnapshot
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public string $scanId,
        public ScanStatus $status,
        public string $createdAt,
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public string $progressLabel = '',
        public ?string $reportId = null,
        public array $errors = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scanId' => $this->scanId,
            'status' => $this->status->value,
            'progressLabel' => $this->progressLabel,
            'createdAt' => $this->createdAt,
            'startedAt' => $this->startedAt,
            'completedAt' => $this->completedAt,
            'reportId' => $this->reportId,
            'errors' => $this->errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            scanId: (string) $data['scanId'],
            status: ScanStatus::from((string) $data['status']),
            createdAt: (string) $data['createdAt'],
            startedAt: $data['startedAt'] ?? null,
            completedAt: $data['completedAt'] ?? null,
            progressLabel: (string) ($data['progressLabel'] ?? ''),
            reportId: $data['reportId'] ?? null,
            errors: $data['errors'] ?? [],
        );
    }
}
