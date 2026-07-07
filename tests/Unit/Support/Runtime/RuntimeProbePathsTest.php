<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Runtime\RuntimeProbePaths;

test('it normalizes runtime probe paths', function () {
    expect(RuntimeProbePaths::normalize(['/healthz', 'catalog/']))
        ->toBe(['/healthz', '/catalog']);
});

test('it throws exception on invalid probe paths', function () {
    expect(fn () => RuntimeProbePaths::normalize(['http://example.com/healthz']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => RuntimeProbePaths::normalize(['/../traversal']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => RuntimeProbePaths::normalize(['']))
        ->toThrow(InvalidArgumentException::class);
});
