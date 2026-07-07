<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Rules\Booted\RuntimeNPlusOneRule;
use kayedspace\Doctor\Support\Runtime\RuntimeProbeContext;
use kayedspace\Doctor\Support\Runtime\RuntimeProbePaths;

test('runtime n plus one rule detects duplicate queries', function () {
    Route::get('/n-plus-one-test', function () {
        DB::select('select 1');
        DB::select('select 1');
        DB::select('select 1');

        return 'OK';
    });

    Route::get('/no-n-plus-one-test', function () {
        DB::select('select 1');

        return 'OK';
    });

    // Test positive
    RuntimeProbeContext::set(new RuntimeProbeContext(
        probePaths: RuntimeProbePaths::normalize(['/n-plus-one-test'])
    ));
    $rule = new RuntimeNPlusOneRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('Duplicate query pattern observed');

    // Test negative
    RuntimeProbeContext::set(new RuntimeProbeContext(
        probePaths: RuntimeProbePaths::normalize(['/no-n-plus-one-test'])
    ));
    $findings = $rule->analyze();
    expect($findings)->toBeEmpty();

    RuntimeProbeContext::clear();
});
