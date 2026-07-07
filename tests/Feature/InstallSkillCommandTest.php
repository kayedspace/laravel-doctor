<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->originalBasePath = $this->app->basePath();
    $this->fixtureDir = $this->fixtureProjectPath();
    app()->setBasePath($this->fixtureDir);
    File::deleteDirectory($this->fixtureDir.'/storage/doctor-skill-test');
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->fixtureDir.'/storage/doctor-skill-test');
});

test('install skill shows destination writes template and protects existing files', function () {
    Config::set('doctor.ai.skill.clients.codex', 'storage/doctor-skill-test/codex/doctor.md');
    File::ensureDirectoryExists($this->fixtureDir.'/storage/doctor-skill-test/codex');

    $destination = $this->fixtureDir.'/storage/doctor-skill-test/codex/doctor.md';

    $this->artisan('doctor:install-skill', ['--client' => 'codex'])
        ->expectsOutputToContain('Destination: '.$destination)
        ->assertExitCode(0);

    expect($destination)->toBeFile()
        ->and(file_get_contents($destination))->toContain('Laravel Doctor AI Workflow');

    file_put_contents($destination, 'existing skill');

    $this->artisan('doctor:install-skill', ['--client' => 'codex'])
        ->expectsConfirmation('Overwrite existing skill file?', false)
        ->expectsOutputToContain('Installation cancelled')
        ->assertExitCode(0);

    expect(file_get_contents($destination))->toBe('existing skill');
});

test('install skill rejects unknown clients and missing destination directories', function () {
    Config::set('doctor.ai.skill.clients.codex', 'storage/doctor-skill-test/missing/doctor.md');
    $destination = $this->fixtureDir.'/storage/doctor-skill-test/missing/doctor.md';

    $this->artisan('doctor:install-skill', ['--client' => 'missing'])
        ->expectsOutputToContain('Unsupported client: missing')
        ->expectsOutputToContain('Supported clients: claude, codex')
        ->assertExitCode(1);

    $this->artisan('doctor:install-skill', ['--client' => 'codex'])
        ->expectsOutputToContain('Destination: '.$destination)
        ->expectsOutputToContain('Destination directory does not exist')
        ->assertExitCode(1);

    expect($this->fixtureDir.'/storage/doctor-skill-test/missing')->not->toBeDirectory()
        ->and($this->fixtureDir.'/.codex')->not->toBeDirectory();
});
