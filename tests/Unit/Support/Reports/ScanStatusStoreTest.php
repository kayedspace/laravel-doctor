<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use kayedspace\Doctor\Domain\Reports\ScanStatus;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;

beforeEach(function () {
    $this->statusProject = sys_get_temp_dir().'/doctor-status-test-'.bin2hex(random_bytes(4));
    mkdir($this->statusProject, 0755, true);

    Config::set('filesystems.disks.local.root', $this->statusProject);
    Storage::forgetDisk('local');

    Config::set('doctor.reports.path', 'storage/doctor');
    Config::set('doctor.reports.status_ttl_seconds', 1);
});

afterEach(function () {
    File::deleteDirectory($this->statusProject);
    Storage::forgetDisk('local');
});

test('scan status store tracks queued running and completed snapshots', function () {
    $store = new ScanStatusStore;

    $queued = $store->create($this->statusProject);
    $running = $store->running($this->statusProject, $queued->scanId);
    $completed = $store->complete($this->statusProject, $queued->scanId, 'report_test');

    expect($queued->status)->toBe(ScanStatus::Queued)
        ->and($running->status)->toBe(ScanStatus::Running)
        ->and($completed->status)->toBe(ScanStatus::Completed)
        ->and($completed->reportId)->toBe('report_test');
});

test('scan status store prunes expired status files', function () {
    $store = new ScanStatusStore;
    $snapshot = $store->create($this->statusProject);
    $disk = Storage::disk('local');
    $path = $disk->path('storage/doctor/scans/'.$snapshot->scanId.'.json');
    touch($path, time() - 3601);

    expect($store->pruneExpired($this->statusProject))->toBe(1);
});
