<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->originalDir = getcwd();
    $this->fixtureDir = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    File::deleteDirectory($this->fixtureDir.'/doctor');
    chdir($this->fixtureDir);
});

afterEach(function () {
    chdir($this->originalDir);
    File::deleteDirectory($this->fixtureDir.'/doctor');
});

test('doctor reports command lists shows deletes and clears reports', function () {
    Artisan::call('doctor:scan', ['--json' => true]);
    $scan = json_decode(Artisan::output(), true);
    $reportId = $scan['savedReport']['reportId'];

    Artisan::call('doctor:reports', ['action' => 'list', '--json' => true]);
    $list = json_decode(Artisan::output(), true);

    expect(array_column($list['reports'], 'reportId'))->toContain($reportId);

    Artisan::call('doctor:reports', [
        'action' => 'show',
        'reportId' => $reportId,
        '--json' => true,
    ]);
    $show = json_decode(Artisan::output(), true);

    expect($show['reportId'])->toBe($reportId);

    expect(Artisan::call('doctor:reports', [
        'action' => 'delete',
        'reportId' => $reportId,
    ]))->toBe(0);

    Artisan::call('doctor:scan', ['--json' => true]);
    expect(Artisan::call('doctor:reports', [
        'action' => 'clear',
        '--force' => true,
    ]))->toBe(0);
});
