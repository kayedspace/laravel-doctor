<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Dependency;

use kayedspace\Doctor\Domain\Enums\RuleId;

class ManifestHealthRule extends AbstractDependencyRule
{
    protected const RULE_ID = RuleId::DependencyManifestHealth;

    public function analyze(array $files = []): array
    {
        $context = $this->context();
        $findings = [];

        foreach ($context->errors as $error) {
            if (str_contains($error, 'composer.json contains invalid JSON')) {
                $findings[] = $this->makeFinding(
                    'composer.json could not be decoded.',
                    $error,
                    'composer-json'
                );
            }
        }

        $errors = $context->validateOutput['errors'] ?? [];
        $valid = $context->validateOutput['valid'] ?? true;
        if ($valid === false || (is_array($errors) && $errors !== [])) {
            foreach (is_array($errors) && $errors !== [] ? $errors : ['Composer validation failed'] as $index => $error) {
                $findings[] = $this->makeFinding(
                    'Composer manifest validation failed.',
                    (string) $error,
                    'validate-'.$index
                );
            }
        }

        return $findings;
    }
}
