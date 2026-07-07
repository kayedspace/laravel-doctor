<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;

class StorageNotWritableRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthStorageNotWritable;

    public function analyze(array $files = []): array
    {
        $findings = [];
        $pathsToCheck = [
            function_exists('storage_path') ? storage_path() : null,
            function_exists('storage_path') ? storage_path('framework/cache') : null,
            function_exists('storage_path') ? storage_path('framework/sessions') : null,
            function_exists('storage_path') ? storage_path('framework/views') : null,
            function_exists('storage_path') ? storage_path('logs') : null,
        ];

        foreach ($pathsToCheck as $path) {
            if ($path === null) {
                continue;
            }

            // If it doesn't exist, we check if we can write to the storage path parent or if the dir itself exists.
            // Under normal Laravel execution, these directories should exist. If they do not, it's also a risk.
            if (! is_dir($path) || ! is_writable($path)) {
                $findings[] = $this->makeFinding(
                    message: "Storage path '{$path}' is not writable or does not exist.",
                    evidence: "path: {$path}"
                );
            }
        }

        return $findings;
    }
}
