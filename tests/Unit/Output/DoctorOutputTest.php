<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Output\DoctorOutput;
use kayedspace\Doctor\Rules\RuleCatalog;

test('doctor finding maps compact fields and redacts secrets', function () {
    $finding = new DoctorFinding(
        id: '123',
        ruleId: 'security.command-injection',
        title: 'Command Injection',
        message: 'Exec call with api_key=secret-token-123',
        severity: Severity::Critical,
        confidence: Confidence::High,
        evidence: 'exec($input)',
        file: 'app/UserController.php',
        line: 12,
        remediation: 'Avoid shell exec'
    );

    $mapped = $finding->toCompactArray();

    expect($mapped)->toBe([
        'rule' => 'security.command-injection',
        'severity' => 'critical',
        'location' => 'app/UserController.php:12',
        'message' => 'Exec call with api_key=[REDACTED]',
    ]);

    expect($finding->toMarkdown())->toContain('### Finding: security.command-injection')
        ->toContain('Rule: security.command-injection')
        ->toContain('Severity: critical')
        ->toContain('Location: app/UserController.php:12')
        ->toContain('Message: Exec call with api_key=[REDACTED]')
        ->toContain('Remediation: Avoid shell exec');
});

test('doctor output groups rules and findings saving tokens', function () {
    $findings = [
        [
            'id' => '1',
            'ruleId' => 'security.command-injection',
            'title' => 'Command Injection',
            'message' => 'Exec call with api_key=secret-token-123',
            'severity' => 'critical',
            'confidence' => 'high',
            'evidence' => 'exec($request->input("command"))',
            'file' => 'app/UserController.php',
            'line' => 12,
            'remediation' => 'Avoid shell execution or pass validated arguments through a safe process API.',
            'tags' => ['security', 'injection'],
        ],
        [
            'id' => '2',
            'ruleId' => 'security.command-injection',
            'title' => 'Command Injection',
            'message' => 'Another exec call',
            'severity' => 'critical',
            'confidence' => 'high',
            'evidence' => 'system($request->input("cmd"))',
            'file' => 'app/AdminController.php',
            'line' => 45,
            'remediation' => 'Avoid shell execution or pass validated arguments through a safe process API.',
            'tags' => ['security', 'injection'],
        ],
        [
            'id' => '3',
            'ruleId' => 'security.command-injection',
            'title' => 'Command Injection',
            'message' => 'Third exec call',
            'severity' => 'critical',
            'confidence' => 'high',
            'evidence' => 'passthru($request->input("cmd"))',
            'file' => 'app/MainController.php',
            'line' => 77,
            'remediation' => 'Avoid shell execution or pass validated arguments through a safe process API.',
            'tags' => ['security', 'injection'],
        ],
        [
            'id' => '4',
            'ruleId' => 'security.command-injection',
            'title' => 'Command Injection',
            'message' => 'Fourth exec call',
            'severity' => 'critical',
            'confidence' => 'high',
            'evidence' => 'shell_exec($request->input("cmd"))',
            'file' => 'app/HelperController.php',
            'line' => 90,
            'remediation' => 'Avoid shell execution or pass validated arguments through a safe process API.',
            'tags' => ['security', 'injection'],
        ],
    ];

    $output = new DoctorOutput($findings);
    $mapped = $output->toCompactArray(app(RuleCatalog::class));

    expect($mapped['rules'])->toHaveKey('security.command-injection')
        ->and($mapped['rules']['security.command-injection']['remediation'])->toBe('Avoid shell execution or pass validated arguments through a safe process API.')
        ->and($mapped['findings'])->toHaveCount(4)
        ->and($mapped['findings'][0]['location'])->toBe('app/UserController.php:12')
        ->and($mapped['findings'][1]['location'])->toBe('app/AdminController.php:45');

    // Verify token size reduction
    $standardJson = json_encode($findings);
    $compactJson = json_encode($mapped);

    $savings = (strlen($standardJson) - strlen($compactJson)) / strlen($standardJson);
    expect($savings)->toBeGreaterThanOrEqual(0.40);
});
