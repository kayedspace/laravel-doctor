<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Fingerprints\FindingFingerprint;

test('finding fingerprint ignores display line movement', function () {
    $first = FindingFingerprint::make('security.raw-sql-interpolation', 'app/Http/UserController.php', 'DB::raw($id)', 12);
    $second = FindingFingerprint::make('security.raw-sql-interpolation', 'app/Http/UserController.php', 'DB::raw($id)', 90);

    expect($first)->toBe($second);
});

test('finding fingerprint rejects unstable absolute paths and normalizes evidence', function () {
    expect(fn () => FindingFingerprint::make('rule', '/tmp/project/file.php', 'secret = value'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');

    expect(FindingFingerprint::make('rule', 'app/File.php', "token = abc\n"))
        ->toBe(FindingFingerprint::make('rule', 'app/File.php', 'token = abc'));
});
