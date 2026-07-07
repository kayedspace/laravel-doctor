<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;

function doctorApi(string $method, string $uri, array $data = []): TestResponse
{
    return test()
        ->withSession(['_token' => 'test-token'])
        ->call($method, $uri, array_merge(['_token' => 'test-token'], $data), [], [], ['HTTP_ACCEPT' => 'application/json']);
}

beforeEach(function () {
    $this->setupFixtureProject();
});

afterEach(function () {
    $this->tearDownFixtureProject();
});

test('api starts a scan and exposes completed status with saved report metadata', function () {
    $response = doctorApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertStatus(202)
        ->assertJsonPath('status', 'completed');

    $scanId = $response->json('scanId');
    expect($response->json('reportId'))->toBeString();

    $this->getJson('/_doctor/api/scans/'.$scanId)
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonStructure(['reportId', 'scanId', 'createdAt', 'status']);
});

test('api returns request errors for unknown rules and metadata endpoints', function () {
    doctorApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
        'rules' => 'missing.rule',
    ])->assertStatus(422)
        ->assertJsonStructure(['error' => ['message']]);

    $this->getJson('/_doctor/api/rules')
        ->assertOk()
        ->assertJsonStructure(['rules' => [['id', 'name', 'category', 'severity', 'confidence', 'capabilities']]]);

    $this->getJson('/_doctor/api/capabilities')
        ->assertOk()
        ->assertJsonPath('reportSaving.httpDeletionEnabled', true);
});

test('api report delete routes return 404 when disabled', function () {
    Config::set('doctor.reports.allow_http_deletes', false);

    doctorApi('DELETE', '/_doctor/api/reports/123')
        ->assertStatus(404)
        ->assertJsonPath('error.message', 'HTTP report deletion is disabled.');

    doctorApi('DELETE', '/_doctor/api/reports')
        ->assertStatus(404)
        ->assertJsonPath('error.message', 'HTTP report deletion is disabled.');
});

test('api lists shows deletes and clears saved reports when http deletes are enabled', function () {
    Config::set('doctor.reports.allow_http_deletes', true);
    $this->setupFixtureProject();

    $scan = doctorApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->json();
    $reportId = $scan['reportId'];

    expect(app('router')->getRoutes()->getByName('doctor.api.reports.delete'))->not->toBeNull()
        ->and(app('router')->getRoutes()->getByName('doctor.api.reports.clear'))->not->toBeNull();

    doctorApi('DELETE', '/_doctor/api/reports/'.$reportId)
        ->assertOk()
        ->assertJsonPath('deleted', true);

    doctorApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ]);

    doctorApi('DELETE', '/_doctor/api/reports')
        ->assertOk()
        ->assertJsonPath('deleted', 1);
});

test('api rejects nested list payloads', function () {
    doctorApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => [['app/Http/Controllers/UserController.php']],
    ])->assertStatus(422)
        ->assertJsonPath('error.message', 'Invalid scan payload: paths must not contain nested values.');
});
