<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

test('api denies production access unless configured gate passes', function () {
    $this->app['env'] = 'production';

    $this->getJson('/_doctor/api/capabilities')->assertForbidden();

    Gate::define('viewDoctor', fn ($user = null): bool => true);

    $this->getJson('/_doctor/api/capabilities')->assertOk();
});

test('dashboard and api can use independent configured gate names', function () {
    $this->app['env'] = 'production';
    Config::set('doctor.ui.gate', 'viewDoctorUi');
    Config::set('doctor.api.gate', 'viewDoctorApi');
    Gate::define('viewDoctorApi', fn ($user = null): bool => true);

    $this->get('/_doctor')->assertForbidden();
    $this->getJson('/_doctor/api/capabilities')->assertOk();
});
