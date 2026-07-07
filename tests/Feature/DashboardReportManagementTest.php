<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;

function dashboardReportScan(array $data): TestResponse
{
    return test()
        ->withSession(['_token' => 'test-token'])
        ->post('/_doctor/scan', array_merge(['_token' => 'test-token'], $data));
}

beforeEach(function () {
    $this->setupFixtureProject();
});

afterEach(function () {
    $this->tearDownFixtureProject();
});

test('dashboard renders rich scan controls filters and report history', function () {
    $this->get('/_doctor')
        ->assertOk()
        ->assertSee('data-doctor-dashboard', false)
        ->assertSee('data-scope-tabs', false)
        ->assertSee('data-finding-search', false)
        ->assertSee('data-report-search', false);
    $redirect = dashboardReportScan([
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertRedirect();

    preg_match('/report_[A-Za-z0-9_]+/', $redirect->headers->get('Location') ?: '', $matches);
    expect($matches[0] ?? null)->toStartWith('report_');

    $this->get($redirect->headers->get('Location'))
        ->assertOk()
        ->assertSee('data-severity-toggle', false)
        ->assertSee($matches[0]);
});

test('dashboard delete controls return error when http deletes are disabled', function () {
    Config::set('doctor.reports.allow_http_deletes', false);

    $this->withSession(['_token' => 'test-token'])
        ->delete('/_doctor/reports/123', ['_token' => 'test-token'])
        ->assertRedirect('/_doctor')
        ->assertSessionHas('doctor_error', 'HTTP report deletion is disabled.');

    $this->withSession(['_token' => 'test-token'])
        ->delete('/_doctor/reports', ['_token' => 'test-token'])
        ->assertRedirect('/_doctor')
        ->assertSessionHas('doctor_error', 'HTTP report deletion is disabled.');
});

test('dashboard delete controls work when http deletes are enabled', function () {
    Config::set('doctor.reports.allow_http_deletes', true);
    $this->setupFixtureProject();

    $redirect = dashboardReportScan([
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertRedirect();

    $this->get($redirect->headers->get('Location'))
        ->assertOk()
        ->assertSee('>Clear all<', false);

    preg_match('/report_[A-Za-z0-9_]+/', $redirect->headers->get('Location') ?: '', $matches);
    $reportId = $matches[0] ?? null;

    expect($reportId)->toStartWith('report_')
        ->and(app('router')->getRoutes()->getByName('doctor.dashboard.reports.delete'))->not->toBeNull();

    $this->withSession(['_token' => 'test-token'])
        ->delete('/_doctor/reports/'.$reportId, ['_token' => 'test-token'])
        ->assertRedirect('/_doctor');
});
