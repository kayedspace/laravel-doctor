<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Reports;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;

/**
 * Tiered retention modeled on spatie/laravel-backup's default cleanup strategy:
 * the newest report always survives; within "keep all" every report survives;
 * beyond that, only the newest report per day/week/month/year survives its tier;
 * anything older than the yearly tier is removed outright.
 */
class ReportRetentionPruner
{
    public function prune(string $projectRoot): int
    {
        $policy = ReportStoragePolicy::fromConfig();
        $resolver = new ReportPathResolver($projectRoot, $policy);
        $disk = $resolver->disk();
        $directory = $resolver->reportsDirectory();

        $files = array_filter(
            $disk->files($directory),
            static fn (string $file): bool => str_ends_with($file, '.json') && ! is_link($disk->path($file))
        );

        if ($files === []) {
            return 0;
        }

        /** @var array<string, Carbon> $dates */
        $dates = [];
        foreach ($files as $file) {
            $dates[$file] = Carbon::createFromTimestamp($disk->lastModified($file));
        }
        uasort($dates, static fn (Carbon $a, Carbon $b): int => $b->timestamp <=> $a->timestamp);

        // Never delete the newest report.
        $newest = array_key_first($dates);
        unset($dates[$newest]);

        $toDelete = array_merge(
            $this->tieredDeletions($dates, $policy),
            $this->expiredDeletions($dates, $policy)
        );

        $deleted = 0;
        foreach (array_keys($toDelete) as $file) {
            $disk->delete($file);
            unset($dates[$file]);
            $deleted++;
        }

        if ($policy->deleteOldestWhenUsingMoreMegabytesThan !== null) {
            $dates[$newest] = Carbon::now();
            $deleted += $this->enforceStorageLimit($disk, $dates, $policy->deleteOldestWhenUsingMoreMegabytesThan);
        }

        return $deleted;
    }

    /**
     * @param  array<string, Carbon>  $dates
     * @return array<string, true>
     */
    private function tieredDeletions(array $dates, ReportStoragePolicy $policy): array
    {
        [$dailyStart, $dailyEnd, $weeklyEnd, $monthlyEnd, $yearlyEnd] = $this->tierBoundaries($policy);

        $tiers = [
            ['start' => $dailyStart, 'end' => $dailyEnd, 'format' => 'Ymd'],
            ['start' => $dailyEnd, 'end' => $weeklyEnd, 'format' => 'YW'],
            ['start' => $weeklyEnd, 'end' => $monthlyEnd, 'format' => 'Ym'],
            ['start' => $monthlyEnd, 'end' => $yearlyEnd, 'format' => 'Y'],
        ];

        $toDelete = [];
        foreach ($tiers as $tier) {
            $inTier = array_filter(
                $dates,
                static fn (Carbon $date): bool => $date->lessThanOrEqualTo($tier['start']) && $date->greaterThan($tier['end'])
            );

            $groups = [];
            foreach ($inTier as $file => $date) {
                $groups[$date->format($tier['format'])][] = $file;
            }

            foreach ($groups as $filesInGroup) {
                foreach (array_slice($filesInGroup, 1) as $file) {
                    $toDelete[$file] = true;
                }
            }
        }

        return $toDelete;
    }

    /**
     * @param  array<string, Carbon>  $dates
     * @return array<string, true>
     */
    private function expiredDeletions(array $dates, ReportStoragePolicy $policy): array
    {
        [, , , , $yearlyEnd] = $this->tierBoundaries($policy);

        $toDelete = [];
        foreach ($dates as $file => $date) {
            if ($date->lessThanOrEqualTo($yearlyEnd)) {
                $toDelete[$file] = true;
            }
        }

        return $toDelete;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon, 4: Carbon}
     */
    private function tierBoundaries(ReportStoragePolicy $policy): array
    {
        $dailyStart = Carbon::now()->subDays($policy->keepAllForDays);
        $dailyEnd = $dailyStart->clone()->subDays($policy->keepDailyForDays);
        $weeklyEnd = $dailyEnd->clone()->subWeeks($policy->keepWeeklyForWeeks);
        $monthlyEnd = $weeklyEnd->clone()->subMonths($policy->keepMonthlyForMonths);
        $yearlyEnd = $monthlyEnd->clone()->subYears($policy->keepYearlyForYears);

        return [$dailyStart, $dailyEnd, $weeklyEnd, $monthlyEnd, $yearlyEnd];
    }

    /**
     * @param  array<string, Carbon>  $remaining
     */
    private function enforceStorageLimit(Filesystem $disk, array $remaining, int $maxMegabytes): int
    {
        uasort($remaining, static fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);

        $totalBytes = 0;
        foreach (array_keys($remaining) as $file) {
            $totalBytes += $disk->size($file);
        }

        $maxBytes = $maxMegabytes * 1024 * 1024;
        $deleted = 0;
        foreach (array_keys($remaining) as $file) {
            if ($totalBytes <= $maxBytes || count($remaining) - $deleted <= 1) {
                break;
            }

            $totalBytes -= $disk->size($file);
            $disk->delete($file);
            $deleted++;
        }

        return $deleted;
    }
}
