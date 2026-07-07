<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Rules\Concerns\HasRuleMetadata;
use kayedspace\Doctor\Rules\RuleSkippedException;
use kayedspace\Doctor\Support\Fingerprints\FindingFingerprint;
use kayedspace\Doctor\Support\Runtime\RuntimeProbeContext;

abstract class AbstractBootedRule implements DoctorRule
{
    use HasRuleMetadata;

    protected const RULE_ID = null;

    /**
     * @throws RuleSkippedException
     */
    protected function requireProbeContext(): RuntimeProbeContext
    {
        $context = RuntimeProbeContext::get();
        if (! $context || empty($context->probePaths)) {
            throw new RuleSkippedException('No explicit read-only HTTP probe paths configured.');
        }

        return $context;
    }

    protected function makeFinding(string $message, string $evidence, ?string $file = null, ?int $line = null, int $index = 0): DoctorFinding
    {
        return DoctorFinding::make($this->id()->value)
            ->id($this->findingId($evidence, $file, $line, $index))
            ->title($this->id()->findingTitle())
            ->message($message)
            ->severity($this->effectiveSeverity())
            ->confidence($this->id()->defaultConfidence())
            ->evidence($evidence)
            ->file($file)
            ->line($line)
            ->remediation($this->id()->remediation())
            ->tags($this->id()->tags());
    }

    protected function findingId(string $evidence, ?string $file, ?int $line, int $index): string
    {
        return FindingFingerprint::make($this->id()->value, $file, $evidence, $line, $index);
    }
}
