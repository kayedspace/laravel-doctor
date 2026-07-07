<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Dependency;

use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Rules\Concerns\HasRuleMetadata;
use kayedspace\Doctor\Rules\RuleSkippedException;
use kayedspace\Doctor\Support\Composer\ComposerAuditContext;
use kayedspace\Doctor\Support\Fingerprints\FindingFingerprint;

abstract class AbstractDependencyRule implements DoctorRule
{
    use HasRuleMetadata;

    protected const RULE_ID = null;

    public function capabilities(): array
    {
        return [RuleCapability::Dependency];
    }

    protected function context(): ComposerAuditContext
    {
        $context = ComposerAuditContext::get();
        if ($context === null) {
            throw new RuleSkippedException('Dependency audit context is not available.');
        }

        return $context;
    }

    protected function skipOnComposerErrors(ComposerAuditContext $context, string $command): void
    {
        if ($context->hasErrorContaining('Composer is not available')
            || $context->hasErrorContaining('composer.lock is missing')
            || $context->hasErrorContaining("Composer {$command} failed")
            || $context->hasErrorContaining("Composer {$command} timed out")
            || $context->hasErrorContaining("Composer {$command} returned invalid JSON")) {
            throw new RuleSkippedException("Composer {$command} output is not available.");
        }
    }

    protected function makeFinding(string $message, string $evidence, string $key, ?string $file = null): DoctorFinding
    {
        return DoctorFinding::make($this->id()->value)
            ->id(FindingFingerprint::make($this->id()->value, $file, $evidence, disambiguator: $key))
            ->title($this->id()->findingTitle())
            ->message($message)
            ->severity($this->effectiveSeverity())
            ->confidence($this->id()->defaultConfidence())
            ->evidence($evidence)
            ->file($file)
            ->remediation($this->id()->remediation())
            ->tags($this->id()->tags());
    }
}
