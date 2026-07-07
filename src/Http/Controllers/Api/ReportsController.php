<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Http\Controllers\Concerns\ApiResponse;
use kayedspace\Doctor\Output\DoctorOutput;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\Support\Reports\ReportStore;
use RuntimeException;

class ReportsController
{
    use ApiResponse;

    public function index(ReportStore $reports): JsonResponse
    {
        try {
            $items = $reports->list($this->projectRoot());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'reports' => array_map(static fn ($metadata): array => $metadata->toArray(), $items),
        ]);
    }

    public function show(string $reportId, ReportStore $reports, Request $request, RuleCatalog $catalog): JsonResponse
    {
        try {
            $report = $reports->get($this->projectRoot(), $reportId);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }

        if ($this->wantsCompact($request)) {
            $output = new DoctorOutput($this->findingArrays($report->data));

            return $this->success($output->toCompactArray($catalog), 200, [], JSON_UNESCAPED_SLASHES);
        }

        return $this->success($report->data);
    }

    public function destroy(string $reportId, ReportStore $reports): JsonResponse
    {
        if (! ReportStoragePolicy::fromConfig()->allowHttpDeletes) {
            return $this->error('HTTP report deletion is disabled.', 404);
        }

        try {
            $deleted = $reports->delete($this->projectRoot(), $reportId);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success([
            'deleted' => $deleted,
            'reportId' => $reportId,
        ]);
    }

    public function clear(ReportStore $reports): JsonResponse
    {
        if (! ReportStoragePolicy::fromConfig()->allowHttpDeletes) {
            return $this->error('HTTP report deletion is disabled.', 404);
        }

        try {
            $deleted = $reports->clear($this->projectRoot());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['deleted' => $deleted]);
    }

    private function projectRoot(): string
    {
        return (new DoctorRequest)->getProjectRoot();
    }

    private function wantsCompact(Request $request): bool
    {
        if ($request->query('format') === 'compact') {
            return true;
        }

        return in_array(strtolower((string) $request->query('compact', '')), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, mixed>>
     */
    private function findingArrays(array $report): array
    {
        return array_values(array_filter(
            is_array($report['findings'] ?? null) ? $report['findings'] : [],
            static fn (mixed $finding): bool => is_array($finding)
        ));
    }
}
