<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFactory;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Http\Controllers\Concerns\ApiResponse;
use kayedspace\Doctor\Http\Support\DoctorRequestFactory;
use kayedspace\Doctor\Output\AiMappedReport;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\ScanOrchestrator;
use kayedspace\Doctor\Support\Reports\ReportStore;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;
use RuntimeException;

class DashboardController
{
    use ApiResponse;

    public function index(ReportStore $reports, RuleCatalog $catalog): View
    {
        return $this->dashboard($reports, $catalog);
    }

    public function scan(
        Request $request,
        DoctorRequestFactory $factory,
        ScanOrchestrator $orchestrator,
        ReportStore $reports,
        RuleCatalog $catalog,
    ): View|RedirectResponse {
        $payload = $this->scanPayload($request);

        try {
            $doctorRequest = $factory->fromPayload($payload);
            $snapshot = $orchestrator->orchestrate($doctorRequest);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->dashboard($reports, $catalog, error: $e->getMessage(), form: $payload);
        }

        if (in_array($snapshot->status->value, ['completed', 'failed'], true) && $snapshot->reportId !== null) {
            return redirect()->route('doctor.dashboard.reports.show', ['reportId' => $snapshot->reportId]);
        }

        return $this->dashboard(
            $reports,
            $catalog,
            scanStatus: $snapshot->toArray(),
            form: $payload,
        );
    }

    public function status(string $scanId, ScanStatusStore $statusStore): JsonResponse
    {
        try {
            $snapshot = $statusStore->get($this->projectRoot(), $scanId);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success($snapshot->toArray());
    }

    public function report(string $reportId, ReportStore $reports, RuleCatalog $catalog): View
    {
        try {
            $report = $reports->get($this->projectRoot(), $reportId);
        } catch (InvalidArgumentException $e) {
            return $this->dashboard($reports, $catalog, error: $e->getMessage());
        }

        return $this->dashboard(
            $reports,
            $catalog,
            currentReport: $report->data,
            selectedReportId: $report->metadata->reportId,
        );
    }

    public function deleteReport(string $reportId, ReportStore $reports): RedirectResponse
    {
        if (! ReportStoragePolicy::fromConfig()->allowHttpDeletes) {
            return redirect()->route('doctor.dashboard')->with('doctor_error', 'HTTP report deletion is disabled.');
        }

        try {
            $reports->delete($this->projectRoot(), $reportId);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('doctor.dashboard')->with('doctor_error', $e->getMessage());
        }

        return redirect()->route('doctor.dashboard')->with('doctor_status', 'Saved report deleted.');
    }

    public function clearReports(ReportStore $reports): RedirectResponse
    {
        if (! ReportStoragePolicy::fromConfig()->allowHttpDeletes) {
            return redirect()->route('doctor.dashboard')->with('doctor_error', 'HTTP report deletion is disabled.');
        }

        try {
            $deleted = $reports->clear($this->projectRoot());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()->route('doctor.dashboard')->with('doctor_error', $e->getMessage());
        }

        return redirect()->route('doctor.dashboard')->with('doctor_status', "{$deleted} saved report(s) deleted.");
    }

    /**
     * @param  array<string, mixed>  $currentReport
     * @param  array<string, mixed>  $scanStatus
     * @param  array<string, mixed>  $form
     */
    private function dashboard(
        ReportStore $reports,
        RuleCatalog $catalog,
        ?array $currentReport = null,
        ?array $scanStatus = null,
        ?string $error = null,
        ?string $selectedReportId = null,
        array $form = [],
    ): View {
        $listError = null;
        $currentReport = $this->withAiCopyPayloads($currentReport);

        try {
            $savedReports = $reports->list($this->projectRoot());
        } catch (InvalidArgumentException|RuntimeException $e) {
            $savedReports = [];
            $listError = $e->getMessage();
        }

        $catalogEntries = $catalog->all();
        $allRules = array_map(fn (array $r): array => [
            'id' => $r['id'],
            'name' => $r['name'],
        ], $catalogEntries);

        $allPacks = array_values(array_unique(array_column($catalogEntries, 'category')));
        $reportPolicy = ReportStoragePolicy::fromConfig();

        $viewName = $currentReport ? 'doctor::report' : 'doctor::home';

        return ViewFactory::make($viewName, [
            'report' => $currentReport,
            'scanStatus' => $scanStatus,
            'savedReports' => array_map(static fn ($metadata): array => $metadata->toArray(), $savedReports),
            'selectedReportId' => $selectedReportId,
            'error' => $error ?? $listError ?? session('doctor_error'),
            'notice' => session('doctor_status'),
            'allRules' => $allRules,
            'allPacks' => $allPacks,
            'projectRoot' => $this->projectRoot(),
            'httpDeletesEnabled' => $reportPolicy->allowHttpDeletes,
            'form' => $form + [
                'scopePreset' => 'full',
                'paths' => '',
                'rules' => '',
                'packs' => '',
                'exclusions' => '',
            ],
            'routes' => [
                'statusTemplate' => route('doctor.dashboard.status', ['scanId' => '__SCAN_ID__'], false),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $report
     * @return array<string, mixed>|null
     */
    private function withAiCopyPayloads(?array $report): ?array
    {
        if ($report === null || ! is_array($report['findings'] ?? null)) {
            return $report;
        }

        $report['findings'] = array_map(
            static function (mixed $finding): mixed {
                if (! is_array($finding)) {
                    return $finding;
                }

                $finding['aiCopyRow'] = AiMappedReport::rowFromArray($finding);

                return $finding;
            },
            $report['findings']
        );

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function scanPayload(Request $request): array
    {
        $payload = [
            'scopePreset' => (string) $request->input('scopePreset', 'full'),
            'paths' => $request->input('paths', ''),
            'rules' => $request->input('rules', ''),
            'packs' => $request->input('packs', ''),
            'exclusions' => $request->input('exclusions', ''),
            'probePaths' => $request->input('probePaths', ''),
            'booted' => $request->input('booted', false),
            'auditDependencies' => $request->input('auditDependencies', false),
        ];

        return $payload;
    }

    private function projectRoot(): string
    {
        return (new DoctorRequest)->getProjectRoot();
    }
}
