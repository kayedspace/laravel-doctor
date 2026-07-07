<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\ScanPlanResolver;

beforeEach(function () {
    $this->originalDir = getcwd();
    $this->fixtureDir = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    chdir($this->fixtureDir);
});

afterEach(function () {
    chdir($this->originalDir);
});

test('command --list-plan shows resolved plan without executing rules', function () {
    Artisan::call('doctor:scan', ['--list-plan' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Analysis plan preview (rules and paths that would be run).');
    expect($output)->toContain('Project Root: '.$this->fixtureDir);
    expect($output)->toContain('Excluded Paths:');
    expect($output)->toContain('Boot Policy: static');
    expect($output)->toContain(RuleId::DevelopmentDebugFunction->value);
    expect($output)->toContain(RuleId::FrameworkEnvOutsideConfig->value);
    expect($output)->toContain(RuleId::MigrationApplicationModel->value);
    expect($output)->toContain(RuleId::SecurityGlobalModelUnguard->value);
});

test('command list-plan json matches the shared scan plan resolver', function () {
    Artisan::call('doctor:scan', [
        '--list-plan' => true,
        '--json' => true,
        '--path' => ['app/Http/Controllers/UserController.php'],
        '--booted' => true,
        '--probe-path' => ['/healthz'],
    ]);

    $data = json_decode(Artisan::output(), true);
    $request = (new DoctorRequest($this->fixtureDir))
        ->withPaths(['app/Http/Controllers/UserController.php'])
        ->withBootPolicy('booted')
        ->withRuntimeProbePaths(['/healthz']);

    expect($data['plan'])->toBe(app(ScanPlanResolver::class)->resolve($request)->toArray());
});

test('command executes static scan and produces console output with findings', function () {
    $exitCode = Artisan::call('doctor:scan');
    $output = Artisan::output();

    expect($exitCode)->toBe(0); // Findings exist but no threshold is set, so exits 0
    expect($output)->toContain('Scan completed with findings:');
    expect($output)->toContain('Rule ID:     '.RuleId::FrameworkEnvOutsideConfig->value);
    expect($output)->toContain('Title:       env() call outside configuration file');
    expect($output)->toContain('Severity:    ERROR');
    expect($output)->toContain('Confidence:  HIGH');
    expect($output)->toContain('File:        app/Http/Controllers/UserController.php');
    expect($output)->toContain('Evidence:    env(\'API_KEY\')');
    expect($output)->toContain('Remediation: Move this environment variable read');
});

test('command --json formats output as JSON and includes findings', function () {
    $exitCode = Artisan::call('doctor:scan', ['--json' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    $data = json_decode($output, true);

    expect($data)->toBeArray();
    expect($data['status'])->toBe('completed');
    expect($data['findings'])->toHaveCount(1);
    expect($data['findings'][0]['ruleId'])->toBe(RuleId::FrameworkEnvOutsideConfig->value);
    expect($data['findings'][0]['file'])->toBe('app/Http/Controllers/UserController.php');
    expect($data['findings'][0]['evidence'])->toBe("env('API_KEY')");

    // Validate that sensitive .env contents are NEVER present in JSON output
    expect($output)->not->toContain('supersecretpassword');
});

test('command honors output format config default', function () {
    Config::set('doctor.output.format', 'json');

    $exitCode = Artisan::call('doctor:scan');
    $output = Artisan::output();
    $data = json_decode($output, true);

    expect($exitCode)->toBe(0);
    expect($data)->toBeArray();
    expect($data['status'])->toBe('completed');
    expect($output)->not->toContain('Laravel Live Doctor analysis engine starting...');
});

test('command --json overrides output format config default', function () {
    Config::set('doctor.output.format', 'console');

    $exitCode = Artisan::call('doctor:scan', ['--json' => true]);
    $output = Artisan::output();
    $data = json_decode($output, true);

    expect($exitCode)->toBe(0);
    expect($data)->toBeArray();
    expect($data['status'])->toBe('completed');
});

test('command explicit format takes precedence over json alias', function () {
    $exitCode = Artisan::call('doctor:scan', [
        '--json' => true,
        '--format' => 'sarif',
    ]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and($data['version'])->toBe('2.1.0')
        ->and($data['runs'][0]['tool']['driver']['name'])->toBe('Laravel Doctor');
});

test('command fails exit policy on severity threshold', function () {
    // UserController.php has Error severity. So fail-on-severity=error should fail.
    $exitCode = Artisan::call('doctor:scan', ['--fail-on-severity' => 'error']);
    expect($exitCode)->toBe(1);

    // UserController.php has Error severity. So fail-on-severity=critical should pass.
    $exitCode2 = Artisan::call('doctor:scan', ['--fail-on-severity' => 'critical']);
    expect($exitCode2)->toBe(0);
});

test('command honors fail_on severity config default', function () {
    Config::set('doctor.fail_on.severity', 'error');

    $exitCode = Artisan::call('doctor:scan');

    expect($exitCode)->toBe(1);
});

test('command --fail-on-severity overrides config default', function () {
    Config::set('doctor.fail_on.severity', 'error');

    $exitCode = Artisan::call('doctor:scan', ['--fail-on-severity' => 'critical']);

    expect($exitCode)->toBe(0);
});

test('command fails exit policy on confidence threshold', function () {
    // UserController.php has High confidence. So fail-on-confidence=high should fail.
    $exitCode = Artisan::call('doctor:scan', ['--fail-on-confidence' => 'high']);
    expect($exitCode)->toBe(1);
});

test('command honors fail_on confidence config default', function () {
    Config::set('doctor.fail_on.confidence', 'high');

    $exitCode = Artisan::call('doctor:scan');

    expect($exitCode)->toBe(1);
});

test('command --fail-on-confidence overrides config default', function () {
    Config::set('doctor.fail_on.confidence', 'low');
    chdir(realpath(__DIR__.'/../Fixtures/projects/static-rules'));

    $exitCode = Artisan::call('doctor:scan', [
        '--rule' => [RuleId::MigrationApplicationModel->value],
        '--fail-on-confidence' => 'high',
    ]);

    expect($exitCode)->toBe(0);
});

test('command honors runtime enabled config default', function () {
    Config::set('doctor.runtime.enabled', true);

    Artisan::call('doctor:scan', ['--list-plan' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Boot Policy: booted');
});

test('command --booted overrides disabled runtime config default', function () {
    Config::set('doctor.runtime.enabled', false);

    Artisan::call('doctor:scan', [
        '--booted' => true,
        '--list-plan' => true,
    ]);
    $output = Artisan::output();

    expect($output)->toContain('Boot Policy: booted');
});

test('command supports booted scan and outputs probe paths in list-plan', function () {
    Artisan::call('doctor:scan', [
        '--booted' => true,
        '--probe-path' => ['/healthz', '/catalog'],
        '--list-plan' => true,
    ]);
    $output = Artisan::output();

    expect($output)->toContain('Boot Policy: booted')
        ->and($output)->toContain('Probe Paths:')
        ->and($output)->toContain('/healthz')
        ->and($output)->toContain('/catalog');
});

test('command runs interactively and prompts for all options', function () {
    Config::set('doctor.runtime.enabled', true);
    Config::set('doctor.dependency_audit.enabled', true);
    Config::set('doctor.fail_on.severity', 'none');
    Config::set('doctor.fail_on.confidence', 'none');
    Config::set('doctor.output.format', 'console');

    $this->artisan('doctor:scan', ['--interactive' => true])
        ->expectsChoice('What scope of files do you want to scan?', 'all', [
            'all' => 'All Project Files',
            'changed' => 'Git Changed Files (uncommitted)',
            'staged' => 'Git Staged Files',
            'base' => 'Git Base (changes since a specific branch/ref)',
            'paths' => 'Specific paths/directories',
        ])
        ->expectsQuestion('Enter paths to exclude (optional, comma-separated, e.g., tests,database):', '')
        ->expectsChoice('Do you want to run specific rules or packs?', 'all', [
            'all' => 'All eligible rules',
            'rules' => 'Specify rules to run',
            'packs' => 'Specify rule packs to run',
        ])
        ->expectsConfirmation('Execute live, booted runtime rule analysis against the application? (Config default: yes)', false)
        ->expectsConfirmation('Opt in to Composer dependency audit checks? (Config default: yes)', false)
        ->expectsChoice('Exit with non-zero status on minimum severity? (Config default: none)', 'none', [
            'none' => 'None',
            'critical' => 'Critical',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
        ])
        ->expectsChoice('Exit with non-zero status on minimum confidence? (Config default: none)', 'none', [
            'none' => 'None',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ])
        ->expectsChoice('Choose output format: (Config default: console)', 'console', [
            'console' => 'Console',
            'json' => 'JSON',
            'sarif' => 'SARIF',
        ])
        ->assertExitCode(0);
});

test('command --no-booted disables booted scan even if runtime enabled is true', function () {
    Config::set('doctor.runtime.enabled', true);

    Artisan::call('doctor:scan', [
        '--no-booted' => true,
        '--list-plan' => true,
    ]);
    $output = Artisan::output();

    expect($output)->toContain('Boot Policy: static');
});

test('command --no-audit disables dependency audit even if enabled is true', function () {
    Config::set('doctor.dependency_audit.enabled', true);

    Artisan::call('doctor:scan', [
        '--no-audit' => true,
        '--list-plan' => true,
    ]);
    $output = Artisan::output();

    // Verify it lists plan and has no dependency capability
    expect($output)->not->toContain('dependency');
});

test('command selects default configured packs when no CLI rules/packs given', function () {
    Config::set('doctor.packs', ['security']);

    Artisan::call('doctor:scan', [
        '--list-plan' => true,
    ]);
    $output = Artisan::output();

    // Only security rules should be selected
    expect($output)->toContain('Selected Rules:')
        ->and($output)->toContain('security.')
        ->and($output)->not->toContain('framework.');
});
