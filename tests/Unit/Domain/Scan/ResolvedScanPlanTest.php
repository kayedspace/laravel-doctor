<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;

test('resolved scan plan holds project details and rules', function () {
    $plan = new ResolvedScanPlan(
        projectRoot: '/home/ali/work/laravel-doctor',
        includedPaths: ['app/'],
        excludedPaths: ['vendor/'],
        selectedRules: [],
        skippedRules: ['rule1' => 'Requires booted Laravel'],
        availableCapabilities: ['static'],
        bootPolicy: 'static'
    );

    expect($plan->getProjectRoot())->toBe('/home/ali/work/laravel-doctor');
    expect($plan->getIncludedPaths())->toBe(['app/']);
    expect($plan->getExcludedPaths())->toBe(['vendor/']);
    expect($plan->getSelectedRules())->toBe([]);
    expect($plan->getSkippedRules())->toBe(['rule1' => 'Requires booted Laravel']);
    expect($plan->getAvailableCapabilities())->toBe(['static']);
    expect($plan->getBootPolicy())->toBe('static');
});
