<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Rules\Booted\LoginNotThrottledRule;

test('login not throttled rule detects unthrottled login routes', function () {
    Route::post('/auth/custom-login-unthrottled', function () {
        return 'OK';
    });

    Route::post('/auth/custom-login-throttled', function () {
        return 'OK';
    })->middleware('throttle:5,1');

    $rule = new LoginNotThrottledRule;
    $findings = $rule->analyze();

    $unthrottledFound = false;
    $throttledFound = false;
    foreach ($findings as $f) {
        if (str_contains($f->message, 'custom-login-unthrottled')) {
            $unthrottledFound = true;
        }
        if (str_contains($f->message, 'custom-login-throttled')) {
            $throttledFound = true;
        }
    }

    expect($unthrottledFound)->toBeTrue()
        ->and($throttledFound)->toBeFalse();
});
