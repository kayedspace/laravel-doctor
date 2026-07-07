<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Tests\Unit\Support\Runtime;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use kayedspace\Doctor\Support\Runtime\HealthSourceResolver;
use Spatie\Health\ResultStores\ResultStore;

// Define mock classes in the Spatie namespace if they don't exist
if (! class_exists('Spatie\Health\ResultStores\ResultStore')) {
    class SpatieHealthResultStoreMock
    {
        /** @var callable */
        public $latestResultsCallback;

        public function latestResults()
        {
            return ($this->latestResultsCallback)();
        }
    }
    class_alias(SpatieHealthResultStoreMock::class, 'Spatie\Health\ResultStores\ResultStore');
}

test('database health check resolver returns true when database is healthy', function () {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('getPdo')->andReturnSelf();

    expect(HealthSourceResolver::isDatabaseHealthy())->toBeTrue();

    DB::clearResolvedInstances();
});

test('database health check resolver returns false when database connection fails', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));

    expect(HealthSourceResolver::isDatabaseHealthy())->toBeFalse();

    DB::clearResolvedInstances();
});

test('cache health check resolver returns true when cache is healthy', function () {
    Cache::shouldReceive('store')->andReturnSelf();
    Cache::shouldReceive('get')->andReturn(null);

    expect(HealthSourceResolver::isCacheHealthy())->toBeTrue();

    Cache::clearResolvedInstances();
});

test('cache health check resolver returns false when cache connection fails', function () {
    Cache::shouldReceive('store')->andThrow(new \Exception('Cache server down'));

    expect(HealthSourceResolver::isCacheHealthy())->toBeFalse();

    Cache::clearResolvedInstances();
});

test('database health check resolver aligns with spatie health results', function () {
    $resultStore = new ResultStore;

    // Test database check fails
    $resultStore->latestResultsCallback = function () {
        $dbCheckResult = new \stdClass;
        $dbCheckResult->name = 'Database';
        $dbCheckResult->status = 'failed';

        $results = new \stdClass;
        $results->checkResults = [$dbCheckResult];

        return $results;
    };

    app()->instance('Spatie\Health\ResultStores\ResultStore', $resultStore);

    // Even if DB connection is healthy, resolver should return false because Spatie check failed
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('getPdo')->andReturnSelf();

    expect(HealthSourceResolver::isDatabaseHealthy())->toBeFalse();

    // Now test database check passes
    $resultStore->latestResultsCallback = function () {
        $dbCheckResult = new \stdClass;
        $dbCheckResult->name = 'Database';
        $dbCheckResult->status = 'ok';

        $results = new \stdClass;
        $results->checkResults = [$dbCheckResult];

        return $results;
    };

    expect(HealthSourceResolver::isDatabaseHealthy())->toBeTrue();

    DB::clearResolvedInstances();
    app()->offsetUnset('Spatie\Health\ResultStores\ResultStore');
});

test('cache health check resolver aligns with spatie health results', function () {
    $resultStore = new ResultStore;

    // Test cache check fails
    $resultStore->latestResultsCallback = function () {
        $cacheCheckResult = new \stdClass;
        $cacheCheckResult->name = 'Cache';
        $cacheCheckResult->status = 'failed';

        $results = new \stdClass;
        $results->checkResults = [$cacheCheckResult];

        return $results;
    };

    app()->instance('Spatie\Health\ResultStores\ResultStore', $resultStore);

    // Even if Cache is healthy, resolver should return false because Spatie check failed
    Cache::shouldReceive('store')->andReturnSelf();
    Cache::shouldReceive('get')->andReturn(null);

    expect(HealthSourceResolver::isCacheHealthy())->toBeFalse();

    // Now test cache check passes
    $resultStore->latestResultsCallback = function () {
        $cacheCheckResult = new \stdClass;
        $cacheCheckResult->name = 'Cache';
        $cacheCheckResult->status = 'ok';

        $results = new \stdClass;
        $results->checkResults = [$cacheCheckResult];

        return $results;
    };

    expect(HealthSourceResolver::isCacheHealthy())->toBeTrue();

    Cache::clearResolvedInstances();
    app()->offsetUnset('Spatie\Health\ResultStores\ResultStore');
});
