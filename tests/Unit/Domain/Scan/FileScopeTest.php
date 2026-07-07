<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\FileScope;

test('file scope distinguishes all files from explicit empty scope', function () {
    expect(FileScope::all()->isConstrained())->toBeFalse()
        ->and(FileScope::all()->paths)->toBe([])
        ->and(FileScope::explicit([])->isConstrained())->toBeTrue()
        ->and(FileScope::explicit([])->paths)->toBe([]);
});

test('file scope sorts and validates project relative paths', function () {
    $scope = FileScope::explicit(['routes/web.php', 'app/Models/User.php'], 'git');

    expect($scope->paths)->toBe(['app/Models/User.php', 'routes/web.php'])
        ->and($scope->source)->toBe('git');

    expect(fn () => FileScope::explicit(['../outside.php']))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');
});
