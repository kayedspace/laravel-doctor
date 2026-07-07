<?php

declare(strict_types=1);

test('composite action exposes ci inputs and runs sarif scan without npm', function () {
    $action = (string) file_get_contents(__DIR__.'/../../action.yml');

    expect($action)->toContain('using: composite')
        ->and($action)->toContain('working-directory:')
        ->and($action)->toContain('scan-args:')
        ->and($action)->toContain('baseline:')
        ->and($action)->toContain('fail-on-severity:')
        ->and($action)->toContain('fail-on-new:')
        ->and($action)->toContain('sarif-path:')
        ->and($action)->toContain('upload-sarif:')
        ->and($action)->toContain('php artisan')
        ->and($action)->toContain('doctor:scan')
        ->and($action)->toContain('--format=sarif')
        ->and($action)->toContain('--baseline=')
        ->and($action)->toContain('--fail-on-severity=')
        ->and($action)->toContain('--fail-on-new')
        ->and($action)->toContain('github/codeql-action/upload-sarif')
        ->and(strtolower($action))->not->toContain('npm');
});
