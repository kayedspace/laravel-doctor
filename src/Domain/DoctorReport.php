<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain;

use kayedspace\Doctor\Domain\Reports\SavedReportMetadata;
use kayedspace\Doctor\Domain\Scan\ReportSummary;
use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;
use kayedspace\Doctor\Domain\Scan\ScanError;

class DoctorReport
{
    protected DoctorRequest $request;

    protected ?ResolvedScanPlan $plan = null;

    protected string $status = 'created';

    protected \DateTimeImmutable $startedAt;

    protected ?\DateTimeImmutable $completedAt = null;

    protected ?SavedReportMetadata $savedReport = null;

    /**
     * @var array<int, DoctorFinding>
     */
    protected array $findings = [];

    /**
     * @var array<string, string>
     */
    protected array $skippedRules = [];

    /**
     * @var array<int, ScanError>
     */
    protected array $errors = [];

    /**
     * Create a new report instance.
     */
    public function __construct(DoctorRequest $request)
    {
        $this->request = $request;
        $this->startedAt = new \DateTimeImmutable;
    }

    /**
     * Get the request context.
     */
    public function getRequest(): DoctorRequest
    {
        return $this->request;
    }

    /**
     * Set the resolved scan plan.
     */
    public function setPlan(ResolvedScanPlan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    /**
     * Get the resolved scan plan.
     */
    public function getPlan(): ?ResolvedScanPlan
    {
        return $this->plan;
    }

    /**
     * Get the scan status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the scan started time.
     */
    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Get the scan completed time.
     */
    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setSavedReport(SavedReportMetadata $metadata): self
    {
        $this->savedReport = $metadata;

        return $this;
    }

    public function getSavedReport(): ?SavedReportMetadata
    {
        return $this->savedReport;
    }

    /**
     * Mark the scan status as completed.
     */
    public function complete(): self
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTimeImmutable;

        return $this;
    }

    /**
     * Mark the scan status as failed.
     */
    public function fail(): self
    {
        $this->status = 'failed';
        $this->completedAt = new \DateTimeImmutable;

        return $this;
    }

    /**
     * Add a finding to the report.
     */
    public function addFinding(DoctorFinding $finding): self
    {
        $this->findings[] = $finding;

        return $this;
    }

    /**
     * Replace report findings with an already-filtered finding set.
     *
     * @param  array<int, DoctorFinding>  $findings
     */
    public function replaceFindings(array $findings): self
    {
        $this->findings = array_values($findings);

        return $this;
    }

    /**
     * Add a skipped rule to the report.
     */
    public function addSkippedRule(string $ruleId, string $reason): self
    {
        $this->skippedRules[$ruleId] = $reason;

        return $this;
    }

    /**
     * Get skipped rules.
     *
     * @return array<string, string>
     */
    public function getSkippedRules(): array
    {
        return $this->skippedRules;
    }

    /**
     * Add a scan error to the report.
     */
    public function addError(ScanError|string $error, ?string $file = null, ?int $line = null): self
    {
        if ($error instanceof ScanError) {
            $this->errors[] = $error;
        } else {
            $this->errors[] = new ScanError($error, $file, $line);
        }

        return $this;
    }

    /**
     * Get scan errors.
     *
     * @return array<int, ScanError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Merge another report's findings into this one.
     */
    public function merge(self $report): self
    {
        $this->findings = array_merge($this->findings, $report->getFindings());
        $this->skippedRules = array_merge($this->skippedRules, $report->getSkippedRules());
        $this->errors = array_merge($this->errors, $report->getErrors());

        return $this;
    }

    /**
     * Get all findings in the report.
     *
     * @return array<int, DoctorFinding>
     */
    public function getFindings(): array
    {
        return $this->findings;
    }

    /**
     * Determine if the report has any findings.
     */
    public function hasFindings(): bool
    {
        return count($this->findings) > 0;
    }

    /**
     * Get the summary DTO by severity.
     */
    public function getSummary(): ReportSummary
    {
        $info = 0;
        $warning = 0;
        $error = 0;
        $critical = 0;

        foreach ($this->findings as $finding) {
            switch ($finding->severity->value) {
                case 'info':
                    $info++;
                    break;
                case 'warning':
                    $warning++;
                    break;
                case 'error':
                    $error++;
                    break;
                case 'critical':
                    $critical++;
                    break;
            }
        }

        return new ReportSummary(
            info: $info,
            warning: $warning,
            error: $error,
            critical: $critical,
            skipped: count($this->skippedRules),
            errors: count($this->errors)
        );
    }

    /**
     * Export the report to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'request' => $this->request->toArray(),
            'plan' => $this->plan ? $this->plan->toArray() : null,
            'status' => $this->status,
            'startedAt' => $this->startedAt->format(\DateTimeInterface::ATOM),
            'completedAt' => $this->completedAt ? $this->completedAt->format(\DateTimeInterface::ATOM) : null,
            'findings' => array_map(fn ($f) => $f->toArray(), $this->findings),
            'skippedRules' => $this->skippedRules,
            'errors' => array_map(fn ($e) => $e->toArray(), $this->errors),
            'summary' => $this->getSummary()->toArray(),
            'savedReport' => $this->savedReport?->toArray(),
        ];
    }
}
