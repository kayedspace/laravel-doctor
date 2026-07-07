<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Dependency;

use kayedspace\Doctor\Domain\Enums\RuleId;

class DevInProductionRule extends AbstractDependencyRule
{
    protected const RULE_ID = RuleId::DependencyDevInProduction;

    public function analyze(array $files = []): array
    {
        $context = $this->context();
        $devRequirements = $context->composerJson['require-dev'] ?? [];
        if (! is_array($devRequirements)) {
            return [];
        }

        $findings = [];
        foreach (array_keys($devRequirements) as $package) {
            $needle = $this->namespaceNeedle((string) $package);
            if ($needle === null) {
                continue;
            }

            foreach ($context->sourceFiles as $sourceFile) {
                if (! str_starts_with($sourceFile->path, 'app/')) {
                    continue;
                }

                if (! str_contains($sourceFile->contents, $needle)) {
                    continue;
                }

                $findings[] = $this->makeFinding(
                    "Application source references require-dev package {$package}.",
                    "{$sourceFile->path} references {$needle} from {$package}",
                    (string) $package.'-'.$sourceFile->path,
                    $sourceFile->path
                );
            }
        }

        return $findings;
    }

    private function namespaceNeedle(string $package): ?string
    {
        return match ($package) {
            'fakerphp/faker' => 'Faker\\',
            'pestphp/pest' => 'Pest\\',
            default => null,
        };
    }
}
