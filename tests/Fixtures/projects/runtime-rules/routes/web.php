<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/secure-headers', function () {
    return response('OK')
        ->header('Strict-Transport-Security', 'max-age=31536000')
        ->header('X-Frame-Options', 'DENY')
        ->header('X-Content-Type-Options', 'nosniff');
});

Route::get('/insecure-headers', function () {
    return response('OK');
});

Route::post('/login', function () {
    return response('OK');
});

Route::post('/login-throttled', function () {
    return response('OK');
})->middleware('throttle:5,1');

Route::get('/n-plus-one', function () {
    // Run DB queries to trigger N+1 query rule
    DB::select('select 1');
    DB::select('select 1');
    DB::select('select 1');

    return response('OK');
});

Route::get('/no-n-plus-one', function () {
    DB::select('select 1');

    return response('OK');
});
