<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;

function dashboardCopyScan(array $data): TestResponse
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

test('dashboard exposes copy one selected and all ai payloads without leaking host paths or secrets', function () {
    $redirect = dashboardCopyScan([
        'scopePreset' => 'manual',
        'paths' => 'app/Http/Controllers/UserController.php',
    ])->assertRedirect();

    $response = $this->get($redirect->headers->get('Location'))
        ->assertOk()
        ->assertSee('data-copy-one', false)
        ->assertSee('data-copy-selected', false)
        ->assertSee('data-copy-all', false)
        ->assertSee('data-finding-select', false)
        ->assertSee('data-ai-copy-payload', false);

    $html = $response->getContent();
    preg_match_all('/data-ai-copy-payload="([^"]*)"/', $html, $matches);
    $payloads = array_map(
        static fn (string $payload): string => base64_decode($payload),
        $matches[1]
    );

    expect(implode("\n", $payloads))->toContain('Move this environment variable read to a configuration file (e.g., config/app.php)')
        ->and($payloads)->not->toBeEmpty()
        ->and(implode("\n", $payloads))->not->toContain($this->fixtureDir)
        ->and(implode("\n", $payloads))->not->toContain('secret-token-123')
        ->and($html)->toContain('data-copy-interactions="1"');
});

test('dashboard copy selected starts disabled and large finding sets are not silently truncated', function () {
    $report = $this->doctorReportWithFindings()->toArray();
    $finding = $report['findings'][0];
    $report['findings'] = array_fill(0, 25, $finding);

    $html = View::make('doctor::report', [
        'report' => $report,
        'scanStatus' => null,
        'savedReports' => [],
        'selectedReportId' => null,
        'error' => null,
        'notice' => null,
        'allRules' => [],
        'allPacks' => [],
        'projectRoot' => $this->fixtureDir,
        'form' => [],
        'routes' => [
            'statusTemplate' => '/_doctor/scans/__SCAN_ID__',
        ],
    ])->render();

    expect($html)->toContain('data-copy-selected')
        ->and($html)->toContain('disabled')
        ->and(substr_count($html, 'data-ai-copy-payload='))->toBe(25)
        ->and(strtolower($html))->not->toContain('truncated');
});
