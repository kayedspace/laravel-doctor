<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\LockStoreHeuristics;

class CacheFlushSharedStoreRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::CacheFlushSharedStore;

    public function analyze(array $files = []): array
    {
        $findings = [];
        $defaultStore = LockStoreHeuristics::defaultStoreName();
        $driver = LockStoreHeuristics::driverFor($defaultStore);
        $prefix = Config::get("cache.stores.{$defaultStore}.prefix", '');

        $sharedDrivers = ['redis', 'memcached', 'database'];
        if (in_array(strtolower($driver), $sharedDrivers, true)) {
            $prefixLower = strtolower(trim($prefix, '_'));
            if ($prefixLower === '' || $prefixLower === 'laravel' || $prefixLower === 'laravelcache') {
                $findings[] = $this->makeFinding(
                    message: "The default cache store '{$defaultStore}' uses driver '{$driver}' with shared store prefix '{$prefix}'. Cache flushes may wipe out other data on the same server.",
                    evidence: "default cache store: {$defaultStore}, driver: {$driver}, prefix: {$prefix}",
                    file: 'config/cache.php'
                );
            }
        }

        return $findings;
    }
}
