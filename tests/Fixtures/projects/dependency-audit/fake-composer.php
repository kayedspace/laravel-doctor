<?php

declare(strict_types=1);

$mode = getenv('DOCTOR_FAKE_COMPOSER_MODE') ?: 'success';
if ($mode === 'timeout') {
    sleep(5);
}
if ($mode === 'fail') {
    fwrite(STDERR, 'composer failed');
    exit(2);
}
if ($mode === 'invalid-json') {
    echo '{not-json';
    exit(0);
}

$command = $argv[1] ?? '';
$file = match ($command) {
    'audit' => 'audit-output.json',
    'outdated' => 'outdated-output.json',
    'validate' => 'validate-output.json',
    default => null,
};

if ($file === null || ! is_file(getcwd().'/'.$file)) {
    echo '{}';
    exit(0);
}

echo file_get_contents(getcwd().'/'.$file);
