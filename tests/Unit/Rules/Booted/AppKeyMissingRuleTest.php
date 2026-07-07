<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Rules\Booted\AppKeyMissingRule;

test('app key missing rule detects issues in production only', function () {
    app()->instance('env', 'local');
    Config::set('app.key', '');

    $rule = new AppKeyMissingRule;
    expect($rule->analyze())->toBeEmpty();

    app()->instance('env', 'production');
    Config::set('app.key', '');

    $findings = $rule->analyze();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('missing or unsafe');

    app()->instance('env', 'testing');
});
