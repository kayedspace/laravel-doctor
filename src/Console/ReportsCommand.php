<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Reports\SavedReportMetadata;
use kayedspace\Doctor\Support\Reports\ReportStore;
use RuntimeException;

use function Laravel\Prompts\confirm;

class ReportsCommand extends Command
{
    protected $signature = 'doctor:reports
                            {action=list : list, show, delete, or clear}
                            {reportId? : Saved report identifier for show/delete}
                            {--json : Emit JSON for list/show}
                            {--force : Skip confirmation for clear}';

    protected $description = 'List, show, delete, or clear saved Laravel Doctor reports';

    public function handle(ReportStore $reports): int
    {
        $action = strtolower((string) $this->argument('action'));

        try {
            return match ($action) {
                'list' => $this->list($reports),
                'show' => $this->show($reports),
                'delete' => $this->delete($reports),
                'clear' => $this->clear($reports),
                default => $this->failWith('Unknown report action. Use list, show, delete, or clear.'),
            };
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->failWith($e->getMessage());
        }
    }

    private function list(ReportStore $reports): int
    {
        $items = $reports->list($this->projectRoot());
        $rows = array_map(static fn (SavedReportMetadata $metadata): array => $metadata->toArray(), $items);

        if ($this->option('json')) {
            $this->line((string) json_encode(['reports' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return 0;
        }

        $this->table(
            ['Report ID', 'Created', 'Status', 'Summary', 'Scope', 'Schema'],
            array_map(fn (SavedReportMetadata $metadata): array => [
                $metadata->reportId,
                $metadata->createdAt ?: '-',
                $metadata->valid ? $metadata->status : 'invalid',
                $metadata->valid ? $this->summary($metadata->summary) : ($metadata->error ?? 'Malformed report'),
                $metadata->scopeLabel,
                $metadata->schemaVersion ?: '-',
            ], $items)
        );

        return 0;
    }

    private function show(ReportStore $reports): int
    {
        $reportId = $this->reportId();
        $report = $reports->get($this->projectRoot(), $reportId);

        if ($this->option('json')) {
            $this->line((string) json_encode($report->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return 0;
        }

        $this->line('Report: '.$report->metadata->reportId);
        $this->line('Created: '.$report->metadata->createdAt);
        $this->line('Status: '.$report->metadata->status);
        $this->line('Summary: '.$this->summary($report->metadata->summary));
        $this->line('Findings: '.count((array) ($report->data['findings'] ?? [])));

        return 0;
    }

    private function delete(ReportStore $reports): int
    {
        $reportId = $this->reportId();
        $reports->delete($this->projectRoot(), $reportId);
        $this->info("Deleted saved report {$reportId}.");

        return 0;
    }

    private function clear(ReportStore $reports): int
    {
        if (! $this->option('force') && ! confirm('Delete all saved Laravel Doctor reports?')) {
            $this->warn('No reports deleted.');

            return 1;
        }

        $deleted = $reports->clear($this->projectRoot());
        $this->info("Deleted {$deleted} saved report(s).");

        return 0;
    }

    private function projectRoot(): string
    {
        return (new DoctorRequest(getcwd()))->getProjectRoot();
    }

    private function reportId(): string
    {
        $reportId = $this->argument('reportId');
        if (! is_string($reportId) || $reportId === '') {
            throw new InvalidArgumentException('Report ID is required.');
        }

        return $reportId;
    }

    /**
     * @param  array<string, int>  $summary
     */
    private function summary(array $summary): string
    {
        return 'critical '.($summary['critical'] ?? 0)
            .', error '.($summary['error'] ?? 0)
            .', warning '.($summary['warning'] ?? 0)
            .', info '.($summary['info'] ?? 0)
            .', errors '.($summary['errors'] ?? 0);
    }

    private function failWith(string $message): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode(['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->error($message);
        }

        return 1;
    }
}
