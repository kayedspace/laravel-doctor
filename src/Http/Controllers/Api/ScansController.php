<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Http\Controllers\Concerns\ApiResponse;
use kayedspace\Doctor\Http\Support\DoctorRequestFactory;
use kayedspace\Doctor\ScanOrchestrator;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;

class ScansController
{
    use ApiResponse;

    public function store(Request $request, DoctorRequestFactory $factory, ScanOrchestrator $orchestrator): JsonResponse
    {
        try {
            $doctorRequest = $factory->fromPayload($request->all());
            $snapshot = $orchestrator->orchestrate($doctorRequest);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        }

        return $this->success($this->withPollUrl($snapshot->toArray()), 202);
    }

    public function show(string $scanId, ScanStatusStore $statusStore): JsonResponse
    {
        try {
            $snapshot = $statusStore->get((new DoctorRequest)->getProjectRoot(), $scanId);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success($this->withPollUrl($snapshot->toArray()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withPollUrl(array $data): array
    {
        $data['pollUrl'] = route('doctor.api.scans.show', ['scanId' => $data['scanId']], false);

        return $data;
    }
}
