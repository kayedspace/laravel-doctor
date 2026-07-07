<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;

test('report storage policy is based on enabled state', function () {
    Config::set('doctor.reports.enabled', true);
    expect(ReportStoragePolicy::fromConfig()->shouldSave())->toBeTrue();

    Config::set('doctor.reports.enabled', false);
    expect(ReportStoragePolicy::fromConfig()->shouldSave())->toBeFalse();
});

test('report storage policy tracks http delete enablement', function () {
    Config::set('doctor.reports.allow_http_deletes', true);
    $policy = ReportStoragePolicy::fromConfig();
    expect($policy->allowHttpDeletes)->toBeTrue();
});
