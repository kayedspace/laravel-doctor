<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Support\Reports\ReportRetentionPruner;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;

class PruneReportsCommand extends Command
{
    protected $signature = 'doctor:prune-reports';

    protected $description = 'Prune saved Laravel Doctor reports and expired scan status beyond the configured retention policy';

    public function handle(ReportRetentionPruner $pruner, ScanStatusStore $statusStore): int
    {
        $projectRoot = (new DoctorRequest(getcwd()))->getProjectRoot();

        $prunedReports = $pruner->prune($projectRoot);
        $prunedStatuses = $statusStore->pruneExpired($projectRoot);

        $this->info("Pruned {$prunedReports} saved report(s) and {$prunedStatuses} expired scan status file(s).");

        return 0;
    }
}
