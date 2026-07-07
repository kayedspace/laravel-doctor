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

test('doctor scan always saves reports', function () {
    Artisan::call('doctor:scan', ['--json' => true]);
    $default = json_decode(Artisan::output(), true);

    expect($default['savedReport']['reportId'] ?? null)->toStartWith('report_');
});
