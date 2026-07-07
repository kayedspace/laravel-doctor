<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\PathResolver;

test('it resolves valid relative paths', function () {
    $resolver = new PathResolver(__DIR__);
    $resolved = $resolver->resolve('PathResolverTest.php');
    expect($resolved)->toBe(realpath(__DIR__.'/PathResolverTest.php'));
});

test('it rejects paths with traversal', function () {
    $resolver = new PathResolver(__DIR__);
    expect(fn () => $resolver->resolve('../Support'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');
});

test('it rejects absolute paths outside project root', function () {
    $resolver = new PathResolver(__DIR__);
    expect(fn () => $resolver->resolve('/etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');
});

test('it rejects absolute sibling paths with the same root prefix', function () {
    $base = sys_get_temp_dir().'/doctor-path-prefix-'.bin2hex(random_bytes(4));
    $root = $base.'/project';
    $sibling = $base.'/project-sibling';

    mkdir($root, 0755, true);
    mkdir($sibling, 0755, true);
    file_put_contents($sibling.'/Outside.php', '<?php');

    try {
        $resolver = new PathResolver($root);

        expect(fn () => $resolver->resolve($sibling.'/Outside.php'))
            ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');
    } finally {
        @unlink($sibling.'/Outside.php');
        @rmdir($sibling);
        @rmdir($root);
        @rmdir($base);
    }
});

test('it rejects symlinks escaping the project root', function () {
    // Create a temporary directory inside project root as project root for testing
    $tempDir = __DIR__.'/temp_root';
    if (! is_dir($tempDir)) {
        mkdir($tempDir);
    }

    // Create a symlink pointing outside temp_root
    $symlinkPath = $tempDir.'/outside_link';
    if (file_exists($symlinkPath)) {
        unlink($symlinkPath);
    }

    // Link to __DIR__ (which is outside temp_root)
    symlink(__DIR__, $symlinkPath);

    $resolver = new PathResolver($tempDir);

    try {
        expect(fn () => $resolver->resolve('outside_link'))
            ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');
    } finally {
        // Clean up
        if (file_exists($symlinkPath)) {
            unlink($symlinkPath);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
});

test('it checks exclusions', function () {
    $resolver = new PathResolver(__DIR__);

    $exclusions = ['vendor/', 'node_modules/', '.env*'];

    expect($resolver->isExcluded('vendor/autoload.php', $exclusions))->toBeTrue();
    expect($resolver->isExcluded('node_modules/lodash/index.js', $exclusions))->toBeTrue();
    expect($resolver->isExcluded('.env', $exclusions))->toBeTrue();
    expect($resolver->isExcluded('.env.example', $exclusions))->toBeTrue();
    expect($resolver->isExcluded('app/Http/Controllers/UserController.php', $exclusions))->toBeFalse();
});
