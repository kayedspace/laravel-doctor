<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

class DiskSpaceLowRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthDiskSpaceLow;

    public function analyze(array $files = []): array
    {
        $thresholdMb = (int) Config::get('doctor.runtime.disk_free_space_threshold_mb', 1024);
        $path = function_exists('base_path') ? base_path() : getcwd();

        try {
            $freeBytes = @disk_free_space($path);
            if ($freeBytes === false) {
                return [];
            }

            $freeMb = $freeBytes / (1024 * 1024);
            if ($freeMb < $thresholdMb) {
                return [
                    $this->makeFinding(
                        message: "Disk space is low on '{$path}'. Free space is ".round($freeMb, 2)." MB, which is below the threshold of {$thresholdMb} MB.",
                        evidence: 'free_space: '.round($freeMb, 2)." MB, threshold: {$thresholdMb} MB"
                    ),
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }
}
