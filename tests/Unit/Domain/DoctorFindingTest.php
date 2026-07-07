<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;

test('it constructs a finding with all required and optional fields', function () {
    $finding = new DoctorFinding(
        id: 'find-1',
        ruleId: 'security.unguarded',
        title: 'Model Unguarded',
        message: 'The model is unguarded',
        severity: Severity::Error,
        confidence: Confidence::High,
        evidence: 'Eloquent::unguard() called',
        file: 'app/Models/User.php',
        line: 12,
        remediation: 'Remove unguard() call',
        tags: ['security', 'eloquent']
    );

    expect($finding->id)->toBe('find-1')
        ->and($finding->ruleId)->toBe('security.unguarded')
        ->and($finding->title)->toBe('Model Unguarded')
        ->and($finding->message)->toBe('The model is unguarded')
        ->and($finding->severity)->toBe(Severity::Error)
        ->and($finding->confidence)->toBe(Confidence::High)
        ->and($finding->evidence)->toBe('Eloquent::unguard() called')
        ->and($finding->file)->toBe('app/Models/User.php')
        ->and($finding->line)->toBe(12)
        ->and($finding->remediation)->toBe('Remove unguard() call')
        ->and($finding->tags)->toBe(['security', 'eloquent']);
});

test('it validates required fields', function () {
    expect(fn () => DoctorFinding::make('rule')->id(''))
        ->toThrow(InvalidArgumentException::class, 'Finding ID must not be empty');

    expect(fn () => DoctorFinding::make('rule')->evidence(''))
        ->toThrow(InvalidArgumentException::class, 'Evidence must not be empty');

    expect(fn () => DoctorFinding::make('rule')->remediation(''))
        ->toThrow(InvalidArgumentException::class, 'Remediation must not be empty');
});

test('it validates location path traversal', function () {
    expect(fn () => DoctorFinding::make('rule')->file('../outside.php'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');

    expect(fn () => DoctorFinding::make('rule')->file('/etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');
});

test('it redacts secrets from evidence when outputting array', function () {
    $finding = new DoctorFinding(
        id: 'id',
        ruleId: 'rule',
        title: 'Title',
        message: 'Msg',
        severity: Severity::Error,
        confidence: Confidence::High,
        evidence: "DB_PASSWORD=supersecret\napi_key: \"secret-token-123\"\nnormal_config=true",
        remediation: 'rem'
    );

    $array = $finding->toArray();

    expect($array['evidence'])->toContain('DB_PASSWORD=[REDACTED]')
        ->and($array['evidence'])->toContain('api_key: "[REDACTED]"')
        ->and($array['evidence'])->toContain('normal_config=true')
        ->and($array['evidence'])->not->toContain('supersecret')
        ->and($array['evidence'])->not->toContain('secret-token-123');
});
