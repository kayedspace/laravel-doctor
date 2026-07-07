<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Support\Reports\ReportRetentionPruner;
use kayedspace\Doctor\Support\Reports\ReportStore;

beforeEach(function () {
    $this->projectRoot = sys_get_temp_dir().'/doctor-retention-test-'.bin2hex(random_bytes(4));
    mkdir($this->projectRoot, 0755, true);

    Config::set('filesystems.disks.local.root', $this->projectRoot);
    Storage::forgetDisk('local');

    Config::set('doctor.reports.enabled', true);
    Config::set('doctor.reports.save_by_default', true);
    Config::set('doctor.reports.path', 'storage/doctor');
    Config::set('doctor.reports.retention.keep_all_for_days', 3650);
});

afterEach(function () {
    File::deleteDirectory($this->projectRoot);
    Storage::forgetDisk('local');
});

test('retention pruner deletes oldest reports once total size exceeds the configured megabyte cap', function () {
    Config::set('doctor.reports.retention.delete_oldest_when_using_more_megabytes_than', 0);

    $store = app(ReportStore::class);
    $first = $store->save((new DoctorReport(new DoctorRequest($this->projectRoot)))->complete());
    $second = $store->save((new DoctorReport(new DoctorRequest($this->projectRoot)))->complete());
    $third = $store->save((new DoctorReport(new DoctorRequest($this->projectRoot)))->complete());

    $disk = Storage::disk('local');
    touch($disk->path('storage/doctor/reports/'.$first->reportId.'.json'), time() - 300);
    touch($disk->path('storage/doctor/reports/'.$second->reportId.'.json'), time() - 200);
    touch($disk->path('storage/doctor/reports/'.$third->reportId.'.json'), time() - 100);

    (new ReportRetentionPruner)->prune($this->projectRoot);

    $remaining = array_column($store->list($this->projectRoot), 'reportId');

    expect($remaining)->toContain($third->reportId)
        ->not->toContain($first->reportId)
        ->not->toContain($second->reportId);
});
