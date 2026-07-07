<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

readonly class ReportSummary
{
    /**
     * Create a new report summary instance.
     */
    public function __construct(
        public int $info = 0,
        public int $warning = 0,
        public int $error = 0,
        public int $critical = 0,
        public int $skipped = 0,
        public int $errors = 0
    ) {}

    /**
     * Convert the summary to an array.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'info' => $this->info,
            'warning' => $this->warning,
            'error' => $this->error,
            'critical' => $this->critical,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
