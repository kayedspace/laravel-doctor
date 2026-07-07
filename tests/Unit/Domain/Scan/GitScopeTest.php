<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\GitScope;

test('git scope exposes changed staged and base modes', function () {
    expect(GitScope::changed()->mode)->toBe('changed')
        ->and(GitScope::changed()->includeUntracked)->toBeTrue()
        ->and(GitScope::staged()->mode)->toBe('staged')
        ->and(GitScope::staged()->includeUntracked)->toBeFalse()
        ->and(GitScope::base('origin/main')->mode)->toBe('base')
        ->and(GitScope::base('origin/main')->baseRef)->toBe('origin/main');
});

test('git scope rejects empty and unsafe base refs', function () {
    expect(fn () => GitScope::base(''))
        ->toThrow(InvalidArgumentException::class, 'Base ref must not be empty');

    expect(fn () => GitScope::base("main\nwhoami"))
        ->toThrow(InvalidArgumentException::class, 'Base ref must not contain control characters');
});
