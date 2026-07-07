<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Support\Reports\ReportStore;

beforeEach(function () {
    $this->reportProject = sys_get_temp_dir().'/doctor-report-test-'.bin2hex(random_bytes(4));
    mkdir($this->reportProject, 0755, true);

    Config::set('filesystems.disks.local.root', $this->reportProject);
    Storage::forgetDisk('local');

    Config::set('doctor.reports.enabled', true);
    Config::set('doctor.reports.save_by_default', true);
    Config::set('doctor.reports.path', 'storage/doctor');
});

afterEach(function () {
    File::deleteDirectory($this->reportProject);
    Storage::forgetDisk('local');
});

test('report store saves lists shows deletes and clears saved reports', function () {
    $store = app(ReportStore::class);
    $report = (new DoctorReport(new DoctorRequest($this->reportProject)))->complete();

    $metadata = $store->save($report);

    expect($metadata->reportId)->toStartWith('report_')
        ->and($metadata->path)->toContain('storage/doctor/reports');

    expect($store->list($this->reportProject))->toHaveCount(1)
        ->and($store->get($this->reportProject, $metadata->reportId)->data['reportId'])->toBe($metadata->reportId);

    expect($store->delete($this->reportProject, $metadata->reportId))->toBeTrue()
        ->and($store->list($this->reportProject))->toBeEmpty();

    $store->save((new DoctorReport(new DoctorRequest($this->reportProject)))->complete());
    expect($store->clear($this->reportProject))->toBe(1);
});

test('report store rejects traversal report ids', function () {
    $store = app(ReportStore::class);
    $report = (new DoctorReport(new DoctorRequest($this->reportProject)))->complete();
    $metadata = $store->save($report);

    expect(fn () => $store->get($this->reportProject, '../'.$metadata->reportId))
        ->toThrow(InvalidArgumentException::class);
});

test('malformed report files list as invalid without hiding valid reports', function () {
    $store = app(ReportStore::class);
    $metadata = $store->save((new DoctorReport(new DoctorRequest($this->reportProject)))->complete());

    $disk = Storage::disk('local');
    file_put_contents($disk->path('storage/doctor/reports/report_broken.json'), '{');
    $reports = $store->list($this->reportProject);

    expect(array_column(array_map(fn ($item) => $item->toArray(), $reports), 'reportId'))
        ->toContain($metadata->reportId, 'report_broken');

    $invalid = collect($reports)->first(fn ($item) => $item->reportId === 'report_broken');
    expect($invalid?->valid)->toBeFalse();
});
