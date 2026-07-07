<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class HealthSourceResolver
{
    /**
     * Resolve database health, aligning with existing sources if present.
     */
    public static function isDatabaseHealthy(): bool
    {
        $timeout = Config::get('doctor.runtime.timeout_seconds', 5);

        // 1. Try to check Spatie's ResultStore first if available
        if (class_exists('Spatie\Health\ResultStores\ResultStore')) {
            try {
                $resultStore = app('Spatie\Health\ResultStores\ResultStore');
                $latestResults = $resultStore->latestResults();
                if ($latestResults && isset($latestResults->checkResults)) {
                    foreach ($latestResults->checkResults as $checkResult) {
                        $isDbCheck = (isset($checkResult->name) && stripos($checkResult->name, 'database') !== false)
                            || (isset($checkResult->meta['check_class']) && stripos($checkResult->meta['check_class'], 'DatabaseCheck') !== false);
                        if ($isDbCheck && isset($checkResult->status) && $checkResult->status === 'failed') {
                            return false;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and fallback
            }
        }

        // 2. Perform a socket check with timeout before connecting, to avoid hanging
        try {
            $default = Config::get('database.default');
            $config = Config::get("database.connections.{$default}");
            if ($config && isset($config['host']) && isset($config['port'])) {
                $host = $config['host'];
                $port = (int) $config['port'];

                $fp = @fsockopen($host, $port, $errno, $errstr, (float) $timeout);
                if (! $fp) {
                    return false;
                }
                fclose($fp);
            }
        } catch (\Throwable $e) {
            // Ignore socket check error and fallback to standard PDO connection check
        }

        // Fallback first-party read check
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Resolve cache health, aligning with existing sources if present.
     */
    public static function isCacheHealthy(): bool
    {
        $timeout = Config::get('doctor.runtime.timeout_seconds', 5);

        // 1. Try to check Spatie's ResultStore first if available
        if (class_exists('Spatie\Health\ResultStores\ResultStore')) {
            try {
                $resultStore = app('Spatie\Health\ResultStores\ResultStore');
                $latestResults = $resultStore->latestResults();
                if ($latestResults && isset($latestResults->checkResults)) {
                    foreach ($latestResults->checkResults as $checkResult) {
                        $isCacheCheck = (isset($checkResult->name) && stripos($checkResult->name, 'cache') !== false)
                            || (isset($checkResult->meta['check_class']) && stripos($checkResult->meta['check_class'], 'CacheCheck') !== false);
                        if ($isCacheCheck && isset($checkResult->status) && $checkResult->status === 'failed') {
                            return false;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and fallback
            }
        }

        // 2. Perform a socket check with timeout for Redis/Memcached stores to avoid hanging
        try {
            $defaultStore = Config::get('cache.default');
            $storeConfig = Config::get("cache.stores.{$defaultStore}");
            if ($storeConfig && isset($storeConfig['driver'])) {
                $driver = $storeConfig['driver'];
                if ($driver === 'redis') {
                    $redisConnection = $storeConfig['connection'] ?? 'default';
                    $redisConfig = Config::get("database.redis.{$redisConnection}");
                    if ($redisConfig && isset($redisConfig['host'])) {
                        $host = $redisConfig['host'];
                        $port = (int) ($redisConfig['port'] ?? 6379);
                        $fp = @fsockopen($host, $port, $errno, $errstr, (float) $timeout);
                        if (! $fp) {
                            return false;
                        }
                        fclose($fp);
                    }
                } elseif ($driver === 'memcached') {
                    $servers = $storeConfig['servers'] ?? [];
                    if (! empty($servers)) {
                        $server = $servers[0];
                        $host = $server['host'] ?? '127.0.0.1';
                        $port = (int) ($server['port'] ?? 11211);
                        $fp = @fsockopen($host, $port, $errno, $errstr, (float) $timeout);
                        if (! $fp) {
                            return false;
                        }
                        fclose($fp);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore socket check error and fallback
        }

        // Fallback first-party read check
        try {
            Cache::store()->get('health_read_reachability_test');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
