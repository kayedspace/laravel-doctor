<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Runtime\SecretRedactor;

test('it redacts secrets from values and arrays', function () {
    expect(SecretRedactor::redact('secret_key=1234567890'))->toBe('secret_key=[REDACTED]');
    expect(SecretRedactor::redactValue('mysecretvalue'))->toBe('[REDACTED]');
    expect(SecretRedactor::redactArray([
        'db_password' => 'secret123',
        'host' => 'localhost',
        'app_key' => 'base64:abc',
        'port' => 3306,
    ]))->toBe([
        'db_password' => '[REDACTED]',
        'host' => 'localhost',
        'app_key' => '[REDACTED]',
        'port' => 3306,
    ]);
});
