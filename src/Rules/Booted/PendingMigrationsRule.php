<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;

class PendingMigrationsRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthPendingMigrations;

    public function analyze(array $files = []): array
    {
        try {
            if (! app()->bound('migrator')) {
                return [];
            }

            $migrator = app('migrator');
            $filesList = $migrator->getMigrationFiles($migrator->paths());

            if (! $migrator->getRepository()->repositoryExists()) {
                $pendingCount = count($filesList);
            } else {
                $ran = $migrator->getRepository()->getRan();
                $pending = array_diff(array_keys($filesList), $ran);
                $pendingCount = count($pending);
            }

            if ($pendingCount > 0) {
                return [
                    $this->makeFinding(
                        message: "There are {$pendingCount} pending database migrations.",
                        evidence: "pending_migrations_count: {$pendingCount}"
                    ),
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }
}
