<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Dependency;

use kayedspace\Doctor\Domain\Enums\RuleId;

class OutdatedPackageRule extends AbstractDependencyRule
{
    protected const RULE_ID = RuleId::DependencyOutdated;

    public function analyze(array $files = []): array
    {
        $context = $this->context();
        $this->skipOnComposerErrors($context, 'outdated');

        $installed = $context->outdatedOutput['installed'] ?? [];
        if (! is_array($installed)) {
            return [];
        }

        $findings = [];
        foreach ($installed as $package) {
            if (! is_array($package)) {
                continue;
            }

            $name = (string) ($package['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $version = (string) ($package['version'] ?? 'installed');
            $latest = (string) ($package['latest'] ?? 'latest');
            $findings[] = $this->makeFinding(
                "Composer package {$name} has a newer release.",
                "{$name} {$version} latest {$latest}",
                $name
            );
        }

        return $findings;
    }
}
