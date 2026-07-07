<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Output\MarkdownReportFormatter;

test('markdown formatter renders one self contained compact block per finding', function () {
    $markdown = (new MarkdownReportFormatter)->format(
        $this->doctorReportWithFindings(),
        new OutputPolicy('markdown')
    );

    expect(substr_count($markdown, '### Finding:'))->toBe(4)
        ->and($markdown)->toContain('Rule: security.command-injection')
        ->and($markdown)->toContain('Severity: critical')
        ->and($markdown)->toContain('Location: app/Http/Controllers/UserController.php:12')
        ->and($markdown)->toContain('Message: User input reaches a shell execution call.')
        ->and($markdown)->toContain('Remediation: Avoid shell execution or pass validated arguments through a safe process API.')
        ->and($markdown)->not->toContain('exec($request->input("command"))');
});

test('markdown formatter redacts copied content', function () {
    $markdown = (new MarkdownReportFormatter)->format(
        $this->doctorReportWithFindings(),
        new OutputPolicy('markdown')
    );

    expect($markdown)->toContain('api_key=[REDACTED]')
        ->and($markdown)->not->toContain('secret-token-123');
});
