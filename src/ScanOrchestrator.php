<?php

declare(strict_types=1);

namespace kayedspace\Doctor;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Domain\Reports\ScanStatusSnapshot;
use kayedspace\Doctor\Jobs\ProcessDoctorScan;
use kayedspace\Doctor\Rules\RuleRegistry;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;
use Throwable;

readonly class ScanOrchestrator
{
    public function __construct(
        private RuleRegistry $registry,
        private DoctorScanAction $scanAction,
        private ScanStatusStore $statusStore,
    ) {}

    public function orchestrate(DoctorRequest $request): ScanStatusSnapshot
    {
        $this->registry->select($request);

        $policy = ReportStoragePolicy::fromConfig();
        $snapshot = $this->statusStore->create($request->getProjectRoot());

        $connection = Config::get('doctor.queue.connection');
        if ($connection !== null) {
            $queue = Config::get('doctor.queue.queue', 'default');
            ProcessDoctorScan::dispatch($snapshot->scanId, $request)
                ->onConnection($connection)
                ->onQueue($queue);
        } else {
            $this->runRecorded($snapshot->scanId, $request);
        }

        return $this->statusStore->get($request->getProjectRoot(), $snapshot->scanId);
    }

    /**
     * Execute the scan and record its lifecycle (running/complete/failed) in the status store.
     */
    public function runRecorded(string $scanId, DoctorRequest $request): void
    {
        $projectRoot = $request->getProjectRoot();
        $this->statusStore->running($projectRoot, $scanId);

        try {
            $report = $this->scanAction->execute($request);

            if ($report->getStatus() === 'failed') {
                $this->statusStore->fail(
                    $projectRoot,
                    $scanId,
                    array_map(static fn ($error): string => (string) $error, $report->getErrors()),
                    $report->getSavedReport()?->reportId,
                );

                return;
            }

            $this->statusStore->complete(
                $projectRoot,
                $scanId,
                $report->getSavedReport()?->reportId,
            );
        } catch (Throwable $e) {
            $this->statusStore->fail($projectRoot, $scanId, [$e->getMessage()]);
        }
    }
}
