<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use kayedspace\Doctor\Domain\Enums\RuleId;

beforeEach(function () {
    $this->originalDir = getcwd();
    chdir(realpath(__DIR__.'/../Fixtures/projects/safe-project'));
});

afterEach(function () {
    chdir($this->originalDir);
});

test('formatter boundary preserves console command output', function () {
    $exitCode = Artisan::call('doctor:scan');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Laravel Live Doctor analysis engine starting...')
        ->and($output)->toContain('Scan completed with findings:')
        ->and($output)->toContain('Rule ID:     '.RuleId::FrameworkEnvOutsideConfig->value)
        ->and($output)->toContain('Summary counts:');
});

test('formatter boundary preserves json command output', function () {
    $exitCode = Artisan::call('doctor:scan', ['--format' => 'json']);
    $output = Artisan::output();
    $data = json_decode($output, true);

    expect($exitCode)->toBe(0)
        ->and($data)->toBeArray()
        ->and($data['status'])->toBe('completed')
        ->and($data['findings'][0]['ruleId'])->toBe(RuleId::FrameworkEnvOutsideConfig->value)
        ->and($output)->not->toContain('Laravel Live Doctor analysis engine starting...');
});
