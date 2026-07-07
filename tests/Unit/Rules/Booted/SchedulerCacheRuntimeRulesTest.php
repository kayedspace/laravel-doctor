<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Rules\Booted\CacheFlushSharedStoreRule;
use kayedspace\Doctor\Rules\Booted\SchedulerSingleServerLockStoreRule;

test('scheduler single server lock store rule detects risks', function () {
    Config::set('cache.default', 'file');
    Config::set('cache.stores.file', ['driver' => 'file']);

    $rule = new SchedulerSingleServerLockStoreRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('single-server');
});

test('cache flush shared store rule detects risks', function () {
    Config::set('cache.default', 'redis');
    Config::set('cache.stores.redis', [
        'driver' => 'redis',
        'prefix' => 'laravel',
    ]);

    $rule = new CacheFlushSharedStoreRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('shared store prefix');
});
