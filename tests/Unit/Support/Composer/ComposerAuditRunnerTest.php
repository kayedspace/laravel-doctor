<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Composer\ComposerAuditRunner;

function fakeComposerCommand(): array
{
    return [PHP_BINARY, __DIR__.'/../../../Fixtures/projects/dependency-audit/fake-composer.php'];
}

test('composer audit runner reads successful composer outputs', function () {
    $root = realpath(__DIR__.'/../../../Fixtures/projects/dependency-audit/vulnerable');

    $context = (new ComposerAuditRunner($root, fakeComposerCommand(), 2))->run();

    expect($context->errors)->toBe([])
        ->and($context->composerJson['require'])->toHaveKey('symfony/http-foundation')
        ->and($context->auditOutput['advisories'])->toHaveKey('symfony/http-foundation')
        ->and($context->outdatedOutput['installed'][0]['name'])->toBe('symfony/http-foundation')
        ->and($context->validateOutput['valid'])->toBeFalse();
});

test('composer audit runner reports missing composer and missing lockfile', function () {
    $root = sys_get_temp_dir().'/doctor-deps-missing-lock-'.bin2hex(random_bytes(6));
    mkdir($root);
    file_put_contents($root.'/composer.json', '{"require":{}}');

    $missingLock = (new ComposerAuditRunner($root, fakeComposerCommand(), 2))->run();
    $missingComposer = (new ComposerAuditRunner($root, ['missing-composer-binary'], 2))->run();

    expect(implode(' ', $missingLock->errors))->toContain('composer.lock is missing')
        ->and(implode(' ', $missingComposer->errors))->toContain('Composer is not available');
});

test('composer audit runner reports invalid json timeout and subprocess failure', function (string $mode, string $message) {
    $root = realpath(__DIR__.'/../../../Fixtures/projects/dependency-audit/vulnerable');

    putenv('DOCTOR_FAKE_COMPOSER_MODE='.$mode);
    try {
        $context = (new ComposerAuditRunner($root, fakeComposerCommand(), 1))->run();
    } finally {
        putenv('DOCTOR_FAKE_COMPOSER_MODE');
    }

    expect(implode(' ', $context->errors))->toContain($message);
})->with([
    ['invalid-json', 'invalid JSON'],
    ['timeout', 'timed out'],
    ['fail', 'failed'],
]);
