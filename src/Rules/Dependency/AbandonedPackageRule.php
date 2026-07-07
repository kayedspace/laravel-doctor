<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Dependency;

use kayedspace\Doctor\Domain\Enums\RuleId;

class AbandonedPackageRule extends AbstractDependencyRule
{
    protected const RULE_ID = RuleId::DependencyAbandonedPackage;

    public function analyze(array $files = []): array
    {
        $context = $this->context();
        $this->skipOnComposerErrors($context, 'audit');

        $abandoned = $context->auditOutput['abandoned'] ?? [];
        if (! is_array($abandoned)) {
            return [];
        }

        $findings = [];
        foreach ($abandoned as $package => $replacement) {
            $replacement = is_string($replacement) && $replacement !== '' ? " Replacement: {$replacement}." : '';
            $findings[] = $this->makeFinding(
                "Composer package {$package} is abandoned.",
                "{$package} is abandoned.{$replacement}",
                (string) $package
            );
        }

        return $findings;
    }
}
