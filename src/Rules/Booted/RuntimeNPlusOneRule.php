<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\RuntimeHttpProbe;
use kayedspace\Doctor\Support\Runtime\RuntimeQueryObserver;

class RuntimeNPlusOneRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::PerformanceRuntimeNPlusOne;

    public function analyze(array $files = []): array
    {
        $context = $this->requireProbeContext();

        $findings = [];

        foreach ($context->probePaths as $path) {
            try {
                RuntimeQueryObserver::start();
                RuntimeHttpProbe::probe($path);
                $queries = RuntimeQueryObserver::stop();

                $counts = [];
                foreach ($queries as $q) {
                    $sql = $q['sql'];
                    $counts[$sql] = ($counts[$sql] ?? 0) + 1;
                }

                foreach ($counts as $sql => $count) {
                    if ($count >= 3) {
                        $findings[] = $this->makeFinding(
                            message: "Duplicate query pattern observed at '{$path}'. The query was executed {$count} times.",
                            evidence: "path: {$path}, sql: {$sql}, count: {$count}"
                        );
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $findings;
    }
}
