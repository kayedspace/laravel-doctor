<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;

/**
 * POST helper that is CSRF-safe across supported Laravel versions (token in session + body).
 */
function doctorScan(array $data): TestResponse
{
    $defaults = array_key_exists('paths', $data) ? ['scopePreset' => 'manual'] : [];

    return test()
        ->withSession(['_token' => 'test-token'])
        ->post('/_doctor/scan', array_merge(['_token' => 'test-token'], $defaults, $data));
}

it('denies the dashboard outside local when no viewDoctor gate is defined', function () {
    $this->app['env'] = 'production';

    $this->get('/_doctor')->assertForbidden();
});

it('allows the dashboard outside local when the viewDoctor gate passes', function () {
    $this->app['env'] = 'production';
    Gate::define('viewDoctor', fn ($user = null) => true);

    $this->get('/_doctor')->assertOk()->assertSee('Laravel Doctor');
});

it('serves the scan form in local', function () {
    $this->app['env'] = 'local';

    $this->get('/_doctor')
        ->assertOk()
        ->assertSee('Laravel Doctor')
        ->assertSee('Run scan');
});

it('runs a scan and renders the report region', function () {
    $this->app['env'] = 'local';

    // Non-existent relative path surfaces as a report error (no throw, no 500).
    $response = doctorScan(['paths' => 'does-not-exist.php'])
        ->assertRedirect();

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('Results')
        ->assertSee('Path does not exist');
});

it('rejects absolute/traversal paths as a shown error, not a 500', function () {
    $this->app['env'] = 'local';

    doctorScan(['paths' => '/etc/passwd'])
        ->assertOk()
        ->assertSee('Path must be project-relative');
});

it('shows invalid dashboard payload errors inline', function () {
    $this->app['env'] = 'local';

    doctorScan(['paths' => 'app/Http/Controllers/UserController.php', 'booted' => 'sometimes'])
        ->assertOk()
        ->assertSee('Invalid scan payload: booted must be a boolean value.');
});

it('never renders the app key or env secrets', function () {
    $this->app['env'] = 'local';
    $secretKey = 'base64:'.base64_encode(random_bytes(32));
    $this->app['config']->set('app.key', $secretKey);

    $this->get('/_doctor')
        ->assertOk()
        ->assertDontSee($secretKey);

    $response = doctorScan(['paths' => 'does-not-exist.php'])
        ->assertRedirect();

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertDontSee($secretKey);
});
