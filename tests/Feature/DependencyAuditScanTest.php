<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

beforeEach(function () {
    $this->originalDir = getcwd();
    Config::set('doctor.dependency_audit.composer_command', [
        PHP_BINARY,
        __DIR__.'/../Fixtures/projects/dependency-audit/fake-composer.php',
    ]);
    Config::set('doctor.dependency_audit.timeout_seconds', 2);
});

afterEach(function () {
    chdir($this->originalDir);
});

test('dependency audit is off by default', function () {
    chdir(realpath(__DIR__.'/../Fixtures/projects/dependency-audit/vulnerable'));

    $exitCode = Artisan::call('doctor:scan', ['--json' => true]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'ruleId'))->not->toContain(RuleId::DependencyKnownVulnerability->value)
        ->and($data['skippedRules'])->not->toHaveKey(RuleId::DependencyKnownVulnerability->value);
});

test('dependency audit runs only when explicitly enabled', function () {
    chdir(realpath(__DIR__.'/../Fixtures/projects/dependency-audit/vulnerable'));

    $exitCode = Artisan::call('doctor:scan', [
        '--audit-dependencies' => true,
        '--json' => true,
    ]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'ruleId'))->toContain(
            RuleId::DependencyKnownVulnerability->value,
            RuleId::DependencyAbandonedPackage->value,
            RuleId::DependencyOutdated->value,
            RuleId::DependencyDevInProduction->value,
            RuleId::DependencyManifestHealth->value,
        );
});
