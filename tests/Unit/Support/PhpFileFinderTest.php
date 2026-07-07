<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use kayedspace\Doctor\Domain\Scan\FileScope;
use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;
use kayedspace\Doctor\Support\PathResolver;
use kayedspace\Doctor\Support\PhpFileFinder;

test('it finds eligible php files and respects exclusions', function () {
    $projectRoot = realpath(__DIR__.'/../../Fixtures/projects/safe-project');
    $resolver = new PathResolver($projectRoot);
    $finder = new PhpFileFinder($resolver);

    $plan = new ResolvedScanPlan(
        projectRoot: $projectRoot,
        includedPaths: [''],
        excludedPaths: ['vendor/', 'storage/'],
        selectedRules: [],
        skippedRules: [],
        availableCapabilities: ['static'],
        bootPolicy: 'static'
    );

    $files = $finder->find($plan);

    // Should include app/Http/Controllers/UserController.php and config/app.php
    // Should NOT include vendor/some-package/src/Helper.php or storage/malformed.php
    expect($files)->toContain('app/Http/Controllers/UserController.php')
        ->and($files)->toContain('config/app.php')
        ->and($files)->not->toContain('vendor/some-package/src/Helper.php')
        ->and($files)->not->toContain('storage/malformed.php');
});

test('it returns no files for an explicit empty file scope', function () {
    $projectRoot = realpath(__DIR__.'/../../Fixtures/projects/safe-project');
    $resolver = new PathResolver($projectRoot);
    $finder = new PhpFileFinder($resolver);

    $plan = new ResolvedScanPlan(
        projectRoot: $projectRoot,
        includedPaths: [],
        excludedPaths: [],
        selectedRules: [],
        skippedRules: [],
        availableCapabilities: ['static'],
        bootPolicy: 'static',
        fileScope: FileScope::explicit([])
    );

    expect($finder->find($plan))->toBe([]);
});

test('it does not discover php files through symlinks escaping the project root', function () {
    $base = sys_get_temp_dir().'/doctor-finder-symlink-'.bin2hex(random_bytes(4));
    $projectRoot = $base.'/project';
    $outside = $base.'/outside';

    mkdir($projectRoot.'/app', 0755, true);
    mkdir($outside, 0755, true);
    file_put_contents($projectRoot.'/app/Safe.php', '<?php');
    file_put_contents($outside.'/Secret.php', '<?php');

    if (! @symlink($outside.'/Secret.php', $projectRoot.'/app/LinkedSecret.php')) {
        File::deleteDirectory($base);
        $this->markTestSkipped('Symlinks are not available.');
    }

    try {
        $finder = new PhpFileFinder(new PathResolver($projectRoot));
        $plan = new ResolvedScanPlan(
            projectRoot: $projectRoot,
            includedPaths: [''],
            excludedPaths: [],
            selectedRules: [],
            skippedRules: [],
            availableCapabilities: ['static'],
            bootPolicy: 'static'
        );

        expect($finder->find($plan))
            ->toContain('app/Safe.php')
            ->not->toContain('app/LinkedSecret.php');
    } finally {
        File::deleteDirectory($base);
    }
});
