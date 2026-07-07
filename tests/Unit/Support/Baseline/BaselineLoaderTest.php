<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Baseline\BaselineLoader;

test('baseline loader reads doctor report fingerprints', function () {
    $root = sys_get_temp_dir().'/doctor-baseline-'.bin2hex(random_bytes(6));
    mkdir($root);
    file_put_contents($root.'/baseline.json', json_encode([
        'findings' => [
            ['id' => 'rule.one'],
            ['id' => 'rule.two'],
        ],
    ]));

    $baseline = (new BaselineLoader($root))->load('baseline.json');

    expect($baseline->contains('rule.one'))->toBeTrue()
        ->and($baseline->contains('missing'))->toBeFalse();
});

test('baseline loader rejects missing traversal and malformed baselines', function () {
    $root = sys_get_temp_dir().'/doctor-baseline-'.bin2hex(random_bytes(6));
    mkdir($root);
    file_put_contents($root.'/bad.json', '{"findings": "nope"}');

    $loader = new BaselineLoader($root);

    expect(fn () => $loader->load('../outside.json'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative')
        ->and(fn () => $loader->load('missing.json'))
        ->toThrow(RuntimeException::class, 'Baseline file is not readable')
        ->and(fn () => $loader->load('bad.json'))
        ->toThrow(RuntimeException::class, 'Baseline file is not a recognizable Doctor report');
});
