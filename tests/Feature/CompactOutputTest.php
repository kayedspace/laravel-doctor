<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;

function compactOutputApi(string $method, string $uri, array $data = []): TestResponse
{
    return test()
        ->withSession(['_token' => 'test-token'])
        ->call($method, $uri, array_merge(['_token' => 'test-token'], $data), [], [], ['HTTP_ACCEPT' => 'application/json']);
}

function compactDashboardScan(array $data): TestResponse
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

test('api report read supports explicit compact format and keeps standard surfaces full', function () {
    $scan = compactOutputApi('POST', '/_doctor/api/scans', [
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertStatus(202)
        ->json();

    $reportId = $scan['reportId'];

    $standard = $this->getJson('/_doctor/api/reports/'.$reportId)
        ->assertOk()
        ->assertJsonPath('reportId', $reportId)
        ->json();

    $compactResponse = $this->getJson('/_doctor/api/reports/'.$reportId.'?format=compact')
        ->assertOk();
    $compact = $compactResponse->json();

    $alias = $this->getJson('/_doctor/api/reports/'.$reportId.'?compact=1')
        ->assertOk()
        ->json();

    expect($standard['findings'][0])->toHaveKeys(['id', 'ruleId', 'title', 'evidence', 'remediation'])
        ->and($compact)->toHaveKeys(['rules', 'findings'])
        ->and($compact['findings'][0])->toHaveKeys(['rule', 'severity', 'location', 'message'])
        ->and(array_keys($compact['findings'][0]))->toBe(['rule', 'severity', 'location', 'message'])
        ->and($alias)->toBe($compact)
        ->and(strlen($compactResponse->getContent()))->toBeLessThan((int) floor(strlen(json_encode($standard)) * 0.6))
        ->and(array_column($compact['findings'], 'rule'))->toBe(array_column($standard['findings'], 'ruleId'))
        ->and(array_column($compact['findings'], 'severity'))->toBe(array_column($standard['findings'], 'severity'));

    Artisan::call('doctor:scan', ['--format' => 'json']);
    $cliJson = json_decode(Artisan::output(), true);

    expect($cliJson['findings'][0])->toHaveKeys(['id', 'ruleId', 'title', 'evidence', 'remediation']);

    $redirect = compactDashboardScan([
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertRedirect();

    $this->get($redirect->headers->get('Location'))
        ->assertOk()
        ->assertSee('Remediation')
        ->assertSee('<pre', false);
});
