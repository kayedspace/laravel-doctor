<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Rules\Booted\DiskSpaceLowRule;
use kayedspace\Doctor\Rules\Booted\StorageNotWritableRule;

test('disk space low rule detects low disk space', function () {
    Config::set('doctor.runtime.disk_free_space_threshold_mb', 99999999);

    $rule = new DiskSpaceLowRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('Disk space is low');
});

test('storage not writable rule detects unwritable storage', function () {
    $rule = new StorageNotWritableRule;
    $findings = $rule->analyze();
    expect($findings)->toBeArray();
});
