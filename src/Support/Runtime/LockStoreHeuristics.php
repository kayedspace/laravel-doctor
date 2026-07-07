<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

use Illuminate\Support\Facades\Config;

class LockStoreHeuristics
{
    /**
     * Determine if a cache lock driver/store is risky for cross-server/worker coordination.
     */
    public static function isRiskyLockStore(?string $driver): bool
    {
        if ($driver === null) {
            return true;
        }

        $risky = ['file', 'array', 'sync', 'database', 'null'];

        return in_array(strtolower($driver), $risky, true);
    }

    public static function defaultStoreName(): string
    {
        return (string) Config::get('cache.default', 'file');
    }

    public static function driverFor(string $storeName): string
    {
        return (string) Config::get("cache.stores.{$storeName}.driver", $storeName);
    }
}
