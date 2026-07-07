<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->setupFixtureProject();
});

afterEach(function () {
    $this->tearDownFixtureProject();
});

test('doctor prune-reports removes reports past the yearly tier while keeping the newest', function () {
    Config::set('doctor.reports.retention.keep_all_for_days', 1);
    Config::set('doctor.reports.retention.keep_daily_for_days', 1);
    Config::set('doctor.reports.retention.keep_weekly_for_weeks', 1);
    Config::set('doctor.reports.retention.keep_monthly_for_months', 1);
    Config::set('doctor.reports.retention.keep_yearly_for_years', 1);

    Artisan::call('doctor:scan', ['--json' => true]);
    $old = json_decode(Artisan::output(), true);
    $oldPath = Storage::disk('local')->path('doctor/reports/'.$old['savedReport']['reportId'].'.json');
    touch($oldPath, time() - (5 * 365 * 86400));

    Artisan::call('doctor:scan', ['--json' => true]);
    $new = json_decode(Artisan::output(), true);

    Artisan::call('doctor:reports', ['action' => 'list', '--json' => true]);
    $beforePrune = json_decode(Artisan::output(), true);
    expect($beforePrune['reports'])->toHaveCount(2);

    expect(Artisan::call('doctor:prune-reports'))->toBe(0);

    Artisan::call('doctor:reports', ['action' => 'list', '--json' => true]);
    $afterPrune = json_decode(Artisan::output(), true);
    expect(array_column($afterPrune['reports'], 'reportId'))
        ->toContain($new['savedReport']['reportId'])
        ->not->toContain($old['savedReport']['reportId']);
});
