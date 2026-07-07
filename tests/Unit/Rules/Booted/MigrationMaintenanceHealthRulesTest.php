<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\MaintenanceMode;
use kayedspace\Doctor\Rules\Booted\MaintenanceModeRule;
use kayedspace\Doctor\Rules\Booted\PendingMigrationsRule;

test('maintenance mode rule detects active maintenance mode', function () {
    $mockMaintenance = Mockery::mock(MaintenanceMode::class);
    $mockMaintenance->shouldReceive('active')->andReturn(true);
    $mockMaintenance->shouldReceive('data')->andReturn([]);
    app()->instance(MaintenanceMode::class, $mockMaintenance);

    $rule = new MaintenanceModeRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('down for maintenance');

    app()->offsetUnset(MaintenanceMode::class);
});

test('pending migrations rule behaves correctly', function () {
    $rule = new PendingMigrationsRule;
    $findings = $rule->analyze();
    expect($findings)->toBeArray();
});
