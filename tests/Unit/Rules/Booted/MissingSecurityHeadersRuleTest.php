<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Rules\Booted\MissingSecurityHeadersRule;
use kayedspace\Doctor\Support\Runtime\RuntimeProbeContext;
use kayedspace\Doctor\Support\Runtime\RuntimeProbePaths;

test('missing security headers rule detects missing headers', function () {
    Route::get('/test-insecure', function () {
        return response('OK');
    });

    Route::get('/test-secure', function () {
        return response('OK')
            ->header('Strict-Transport-Security', 'max-age=31536000')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff');
    });

    // Test insecure
    RuntimeProbeContext::set(new RuntimeProbeContext(
        probePaths: RuntimeProbePaths::normalize(['/test-insecure'])
    ));
    $rule = new MissingSecurityHeadersRule;
    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('Strict-Transport-Security')
        ->and($findings[0]->message)->toContain('X-Frame-Options')
        ->and($findings[0]->message)->toContain('X-Content-Type-Options');

    // Test secure
    RuntimeProbeContext::set(new RuntimeProbeContext(
        probePaths: RuntimeProbePaths::normalize(['/test-secure'])
    ));
    $findings = $rule->analyze();
    expect($findings)->toBeEmpty();

    RuntimeProbeContext::clear();
});
