<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Rules\Booted\QueueDispatchBeforeCommitRule;
use kayedspace\Doctor\Rules\Booted\QueueTimeoutRetryAfterRule;
use kayedspace\Doctor\Rules\Booted\QueueUniqueLockStoreRule;

test('queue timeout retry after rule detects risks', function () {
    Config::set('queue.connections', [
        'test_connection' => [
            'driver' => 'redis',
            'timeout' => 60,
            'retry_after' => 60,
        ],
    ]);

    $rule = new QueueTimeoutRetryAfterRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('test_connection');
});

test('queue dispatch before commit rule detects risks', function () {
    Config::set('queue.connections', [
        'test_connection' => [
            'driver' => 'redis',
            'after_commit' => false,
        ],
    ]);

    $rule = new QueueDispatchBeforeCommitRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('test_connection');
});

test('queue unique lock store rule detects risks', function () {
    Config::set('cache.default', 'file');
    Config::set('cache.stores.file', ['driver' => 'file']);

    $rule = new QueueUniqueLockStoreRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('unique job locking');
});
