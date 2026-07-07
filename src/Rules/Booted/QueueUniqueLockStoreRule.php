<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\LockStoreHeuristics;

class QueueUniqueLockStoreRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::QueueUniqueLockStore;

    public function analyze(array $files = []): array
    {
        $findings = [];
        $defaultStore = LockStoreHeuristics::defaultStoreName();
        $driver = LockStoreHeuristics::driverFor($defaultStore);

        if (LockStoreHeuristics::isRiskyLockStore($driver)) {
            $findings[] = $this->makeFinding(
                message: "The default cache store '{$defaultStore}' uses the '{$driver}' driver, which is risky for unique job locking in multi-server or production environments.",
                evidence: "default cache store: {$defaultStore}, driver: {$driver}",
                file: 'config/cache.php'
            );
        }

        return $findings;
    }
}
