<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Rules\Booted\UnsafeDriverRule;

test('unsafe driver rule detects unsafe drivers in production only', function () {
    app()->instance('env', 'local');
    Config::set('session.driver', 'file');

    $rule = new UnsafeDriverRule;
    expect($rule->analyze())->toBeEmpty();

    app()->instance('env', 'production');
    Config::set('session.driver', 'file');
    Config::set('queue.default', 'sync_conn');
    Config::set('queue.connections.sync_conn', ['driver' => 'sync']);
    Config::set('cache.default', 'file_store');
    Config::set('cache.stores.file_store', ['driver' => 'file']);

    $findings = $rule->analyze();
    expect($findings)->toHaveCount(3);

    app()->instance('env', 'testing');
});
