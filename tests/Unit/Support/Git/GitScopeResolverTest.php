<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\GitScope;
use kayedspace\Doctor\Support\Git\GitScopeResolver;
use kayedspace\Doctor\Tests\Support\GitFixture;

test('git scope resolver finds changed staged and untracked php files', function () {
    $repo = new GitFixture;
    $repo->write('app/Existing.php', '<?php echo 1;');
    $repo->write('README.md', '# docs');
    $repo->commit();

    $repo->write('app/Existing.php', '<?php echo 2;');
    $repo->write('app/New.php', '<?php echo 3;');
    $repo->write('docs/readme.md', 'ignore');
    $repo->run('git add app/Existing.php');

    $resolver = new GitScopeResolver($repo->root);

    expect($resolver->resolve(GitScope::changed())->paths)->toBe(['app/Existing.php', 'app/New.php'])
        ->and($resolver->resolve(GitScope::staged())->paths)->toBe(['app/Existing.php']);
});

test('git scope resolver handles base diff and working tree changes', function () {
    $repo = new GitFixture;
    $repo->write('app/Base.php', '<?php echo 1;');
    $repo->commit('base');
    $base = trim($repo->run('git rev-parse HEAD'));

    $repo->write('app/Branch.php', '<?php echo 2;');
    $repo->commit('branch');
    $repo->write('app/Worktree.php', '<?php echo 3;');

    $resolver = new GitScopeResolver($repo->root);

    expect($resolver->resolve(GitScope::base($base))->paths)->toBe(['app/Branch.php', 'app/Worktree.php']);
});

test('git scope resolver ignores deleted files and preserves explicit zero file scopes', function () {
    $repo = new GitFixture;
    $repo->write('app/Deleted.php', '<?php echo 1;');
    $repo->commit('base');
    unlink($repo->root.'/app/Deleted.php');

    $resolver = new GitScopeResolver($repo->root);

    expect($resolver->resolve(GitScope::changed())->paths)->toBe([]);

    $repo->commit('delete');

    expect($resolver->resolve(GitScope::changed())->paths)->toBe([]);
});

test('git scope resolver fails clearly outside git or when git cannot run', function () {
    $dir = sys_get_temp_dir().'/doctor-not-git-'.bin2hex(random_bytes(6));
    mkdir($dir);

    expect(fn () => (new GitScopeResolver($dir))->resolve(GitScope::changed()))
        ->toThrow(RuntimeException::class, 'not a git repository');

    expect(fn () => (new GitScopeResolver($dir, 'missing-git-binary'))->resolve(GitScope::changed()))
        ->toThrow(RuntimeException::class, 'git is not available');
});

test('git scope resolver reports git command failures', function () {
    $repo = new GitFixture;
    $repo->write('app/Base.php', '<?php echo 1;');
    $repo->commit('base');

    expect(fn () => (new GitScopeResolver($repo->root))->resolve(GitScope::base('missing-ref')))
        ->toThrow(RuntimeException::class, 'git command failed');
});
