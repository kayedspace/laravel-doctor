<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use kayedspace\Doctor\Rules\Booted\CacheUnreachableRule;
use kayedspace\Doctor\Rules\Booted\DatabaseUnreachableRule;

test('database unreachable rule reports finding on connection failure', function () {
    DB::shouldReceive('connection')->andThrow(new Exception('Connection failed'));

    $rule = new DatabaseUnreachableRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('Database connection is unreachable');

    DB::clearResolvedInstances();
});

test('cache unreachable rule reports finding on connection failure', function () {
    Cache::shouldReceive('store')->andThrow(new Exception('Cache server down'));

    $rule = new CacheUnreachableRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('Cache service is unreachable');

    Cache::clearResolvedInstances();
});
