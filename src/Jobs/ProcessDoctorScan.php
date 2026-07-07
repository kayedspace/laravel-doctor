<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\ScanOrchestrator;

class ProcessDoctorScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $scanId,
        public DoctorRequest $request
    ) {}

    public function handle(ScanOrchestrator $orchestrator): void
    {
        $orchestrator->runRecorded($this->scanId, $this->request);
    }
}
