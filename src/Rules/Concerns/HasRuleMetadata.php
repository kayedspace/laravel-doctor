<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Concerns;

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;

trait HasRuleMetadata
{
    protected ?Severity $severityOverride = null;

    public function id(): RuleId
    {
        if (static::RULE_ID === null) {
            throw new \LogicException(static::class.' must define RULE_ID.');
        }

        return static::RULE_ID;
    }

    public function name(): string
    {
        return $this->id()->ruleName();
    }

    public function description(): string
    {
        return $this->id()->description();
    }

    public function category(): RuleCategory
    {
        return $this->id()->category();
    }

    public function defaultSeverity(): Severity
    {
        return $this->id()->defaultSeverity();
    }

    public function defaultConfidence(): Confidence
    {
        return $this->id()->defaultConfidence();
    }

    public function capabilities(): array
    {
        return $this->id()->capabilities();
    }

    public function isBeta(): bool
    {
        return $this->id()->isBeta();
    }

    public function remediation(): string
    {
        return $this->id()->remediation();
    }

    public function examples(): array
    {
        return $this->id()->examples();
    }

    public function withSeverityOverride(?Severity $severity): static
    {
        $this->severityOverride = $severity;

        return $this;
    }

    public function effectiveSeverity(): Severity
    {
        return $this->severityOverride ?? $this->defaultSeverity();
    }
}
