<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;

readonly class OutputPolicy
{
    /**
     * Create a new output policy instance.
     */
    public function __construct(
        public string $format = 'console',
        public ?Severity $failOnSeverity = null,
        public ?Confidence $failOnConfidence = null,
        public bool $failOnNew = false,
        public ?string $baselinePath = null,
    ) {
        if (! in_array($format, ['console', 'json', 'sarif', 'markdown', 'compact-json'], true)) {
            throw new \InvalidArgumentException('Unsupported output format');
        }

        if ($failOnNew && $baselinePath === null) {
            throw new \InvalidArgumentException('Baseline required when fail-on-new is enabled');
        }
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFailOnSeverity(): ?Severity
    {
        return $this->failOnSeverity;
    }

    public function getFailOnConfidence(): ?Confidence
    {
        return $this->failOnConfidence;
    }

    public function shouldFailOnNew(): bool
    {
        return $this->failOnNew;
    }

    public function getBaselinePath(): ?string
    {
        return $this->baselinePath;
    }

    /**
     * Export the policy to array.
     *
     * @return array<string, bool|string|null>
     */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'failOnSeverity' => $this->failOnSeverity?->value,
            'failOnConfidence' => $this->failOnConfidence?->value,
            'failOnNew' => $this->failOnNew,
            'baselinePath' => $this->baselinePath,
        ];
    }

    /**
     * Determine if the report findings should trigger a command line failure.
     */
    public function shouldFail(DoctorReport $report): bool
    {
        if ($this->failOnNew && $report->hasFindings()) {
            return true;
        }

        foreach ($report->getFindings() as $finding) {
            if ($this->failOnSeverity !== null && $finding->severity->weight() >= $this->failOnSeverity->weight()) {
                return true;
            }
            if ($this->failOnConfidence !== null && $finding->confidence->weight() >= $this->failOnConfidence->weight()) {
                return true;
            }
        }

        return false;
    }
}
