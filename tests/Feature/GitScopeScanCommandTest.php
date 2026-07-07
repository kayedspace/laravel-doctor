<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use kayedspace\Doctor\Tests\Support\GitFixture;

beforeEach(function () {
    $this->originalDir = getcwd();
});

afterEach(function () {
    chdir($this->originalDir);
});

test('command scans only changed php files', function () {
    $repo = new GitFixture;
    $repo->write('app/Old.php', "<?php env('OLD_KEY');");
    $repo->commit('old');
    $repo->write('app/New.php', "<?php env('NEW_KEY');");
    chdir($repo->root);

    $exitCode = Artisan::call('doctor:scan', ['--changed' => true, '--json' => true]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'file'))->toBe(['app/New.php']);
});

test('command scans only staged php files', function () {
    $repo = new GitFixture;
    $repo->write('app/Old.php', "<?php env('OLD_KEY');");
    $repo->commit('old');
    $repo->write('app/Staged.php', "<?php env('STAGED_KEY');");
    $repo->write('app/Unstaged.php', "<?php env('UNSTAGED_KEY');");
    $repo->run('git add app/Staged.php');
    chdir($repo->root);

    $exitCode = Artisan::call('doctor:scan', ['--staged' => true, '--json' => true]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'file'))->toBe(['app/Staged.php']);
});

test('command fails git scope outside git repository', function () {
    $dir = sys_get_temp_dir().'/doctor-not-git-'.bin2hex(random_bytes(6));
    mkdir($dir);
    chdir($dir);

    $exitCode = Artisan::call('doctor:scan', ['--changed' => true]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('not a git repository');
});

test('command intersects git scope with path filter', function () {
    $repo = new GitFixture;
    $repo->write('app/Included.php', "<?php env('INCLUDED_KEY');");
    $repo->write('routes/Ignored.php', "<?php env('IGNORED_KEY');");
    $repo->commit('base');
    $repo->write('app/Included.php', "<?php env('INCLUDED_CHANGED');");
    $repo->write('routes/Ignored.php', "<?php env('IGNORED_CHANGED');");
    chdir($repo->root);

    $exitCode = Artisan::call('doctor:scan', ['--changed' => true, '--path' => ['app'], '--json' => true]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'file'))->toBe(['app/Included.php']);
});

test('command scans base scope and uncommitted worktree files', function () {
    $repo = new GitFixture;
    $repo->write('app/Base.php', "<?php env('BASE_KEY');");
    $repo->commit('base');
    $base = trim($repo->run('git rev-parse HEAD'));
    $repo->write('app/Branch.php', "<?php env('BRANCH_KEY');");
    $repo->commit('branch');
    $repo->write('app/Worktree.php', "<?php env('WORKTREE_KEY');");
    chdir($repo->root);

    $exitCode = Artisan::call('doctor:scan', ['--base' => $base, '--json' => true]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'file'))->toBe(['app/Branch.php', 'app/Worktree.php']);
});

test('command preserves empty git scopes and reports git command failures', function () {
    $repo = new GitFixture;
    $repo->write('app/Clean.php', "<?php env('CLEAN_KEY');");
    $repo->commit('clean');
    chdir($repo->root);

    $emptyExit = Artisan::call('doctor:scan', ['--changed' => true, '--json' => true]);
    $empty = json_decode(Artisan::output(), true);
    $failedExit = Artisan::call('doctor:scan', ['--base' => 'missing-ref']);

    expect($emptyExit)->toBe(0)
        ->and($empty['findings'])->toBe([])
        ->and($empty['plan']['fileScope']['isConstrained'])->toBeTrue()
        ->and($failedExit)->toBe(1)
        ->and(Artisan::output())->toContain('git command failed');
});

test('command applies exclusions after git scope', function () {
    $repo = new GitFixture;
    $repo->write('app/Excluded.php', "<?php env('EXCLUDED_KEY');");
    $repo->write('routes/Included.php', "<?php env('INCLUDED_KEY');");
    $repo->commit('base');
    $repo->write('app/Excluded.php', "<?php env('EXCLUDED_CHANGED');");
    $repo->write('routes/Included.php', "<?php env('INCLUDED_CHANGED');");
    chdir($repo->root);

    $exitCode = Artisan::call('doctor:scan', [
        '--changed' => true,
        '--exclude' => ['app/'],
        '--json' => true,
    ]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and(array_column($data['findings'], 'file'))->toBe(['routes/Included.php']);
});
