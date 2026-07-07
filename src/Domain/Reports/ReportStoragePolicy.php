<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Reports;

use Illuminate\Support\Facades\Config;

readonly class ReportStoragePolicy
{
    public function __construct(
        public bool $enabled,
        public bool $allowHttpDeletes,
        public string $path,
        public string $disk,
        public int $keepAllForDays = 7,
        public int $keepDailyForDays = 16,
        public int $keepWeeklyForWeeks = 8,
        public int $keepMonthlyForMonths = 4,
        public int $keepYearlyForYears = 2,
        public ?int $deleteOldestWhenUsingMoreMegabytesThan = null,
    ) {}

    public static function fromConfig(): self
    {
        $retention = Config::get('doctor.reports.retention', []);
        $maxMegabytes = $retention['delete_oldest_when_using_more_megabytes_than'] ?? null;

        return new self(
            enabled: (bool) Config::get('doctor.reports.enabled', true),
            allowHttpDeletes: (bool) Config::get('doctor.reports.allow_http_deletes', false),
            path: (string) Config::get('doctor.reports.path', 'doctor'),
            disk: (string) Config::get('doctor.reports.disk', 'local'),
            keepAllForDays: (int) ($retention['keep_all_for_days'] ?? 7),
            keepDailyForDays: (int) ($retention['keep_daily_for_days'] ?? 16),
            keepWeeklyForWeeks: (int) ($retention['keep_weekly_for_weeks'] ?? 8),
            keepMonthlyForMonths: (int) ($retention['keep_monthly_for_months'] ?? 4),
            keepYearlyForYears: (int) ($retention['keep_yearly_for_years'] ?? 2),
            deleteOldestWhenUsingMoreMegabytesThan: $maxMegabytes !== null ? (int) $maxMegabytes : null,
        );
    }

    public function reportsPath(): string
    {
        return trim($this->path, '/').'/reports';
    }

    public function statusPath(): string
    {
        return trim($this->path, '/').'/scans';
    }

    public function shouldSave(): bool
    {
        return $this->enabled;
    }
}
