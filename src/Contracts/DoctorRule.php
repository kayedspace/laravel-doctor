<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Contracts;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\SourceFile;

interface DoctorRule
{
    /**
     * Get the unique identifier for the rule.
     */
    public function id(): RuleId;

    /**
     * Get the name of the rule.
     */
    public function name(): string;

    /**
     * Get a description of what the rule checks for.
     */
    public function description(): string;

    /**
     * Get the category of the rule.
     */
    public function category(): RuleCategory;

    /**
     * Get the default severity of the rule.
     */
    public function defaultSeverity(): Severity;

    /**
     * Get the default confidence of the rule.
     */
    public function defaultConfidence(): Confidence;

    /**
     * Get the remediation text for the rule.
     */
    public function remediation(): string;

    /**
     * Get illustrative examples for the rule.
     *
     * @return array<int, string>
     */
    public function examples(): array;

    /**
     * Get the declared capabilities of the rule.
     *
     * @return array<int, RuleCapability>
     */
    public function capabilities(): array;

    /**
     * Get whether the rule is beta/advisory.
     */
    public function isBeta(): bool;

    /**
     * Execute the rule analysis.
     *
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array;
}
