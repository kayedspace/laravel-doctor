<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Reports;

use DateTimeImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Domain\Reports\SavedReport;
use kayedspace\Doctor\Domain\Reports\SavedReportMetadata;
use RuntimeException;
use Throwable;

class ReportStore
{
    public function __construct(
        private readonly ReportSerializer $serializer,
    ) {}

    public function save(DoctorReport $report): SavedReportMetadata
    {
        $policy = ReportStoragePolicy::fromConfig();
        if (! $policy->shouldSave()) {
            throw new RuntimeException('Report saving is disabled for this scan.');
        }

        $resolver = new ReportPathResolver($report->getRequest()->getProjectRoot(), $policy);
        $createdAt = new DateTimeImmutable;
        $reportId = 'report_'.$createdAt->format('YmdHis').'_'.bin2hex(random_bytes(4));
        $data = $this->serializer->serialize($report, $reportId, $createdAt);
        $path = $resolver->reportPath($reportId);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || ! $resolver->disk()->put($path, $json)) {
            throw new RuntimeException('Could not write saved report.');
        }

        return $this->serializer->metadata($data, $path);
    }

    /**
     * @return array<int, SavedReportMetadata>
     */
    public function list(string $projectRoot): array
    {
        $resolver = new ReportPathResolver($projectRoot, ReportStoragePolicy::fromConfig());
        $disk = $resolver->disk();
        $directory = $resolver->reportsDirectory();
        $files = array_values(array_filter(
            $disk->files($directory),
            static fn (string $file): bool => str_ends_with($file, '.json')
        ));
        rsort($files);

        $reports = [];
        foreach ($files as $file) {
            $data = $this->readJson($disk, $file);
            if ($data === null) {
                $reports[] = new SavedReportMetadata(
                    reportId: basename($file, '.json'),
                    createdAt: '',
                    status: 'invalid',
                    summary: [],
                    scopeLabel: 'Invalid report',
                    schemaVersion: '',
                    path: $file,
                    valid: false,
                    error: 'Saved report is malformed.',
                );

                continue;
            }

            try {
                $reports[] = $this->serializer->metadata($data, $file);
            } catch (InvalidArgumentException $e) {
                $reports[] = new SavedReportMetadata(
                    reportId: basename($file, '.json'),
                    createdAt: '',
                    status: 'invalid',
                    summary: [],
                    scopeLabel: 'Invalid report',
                    schemaVersion: '',
                    path: $file,
                    valid: false,
                    error: $e->getMessage(),
                );
            }
        }

        usort(
            $reports,
            fn (SavedReportMetadata $a, SavedReportMetadata $b): int => strcmp($b->createdAt, $a->createdAt)
        );

        return $reports;
    }

    public function get(string $projectRoot, string $reportId): SavedReport
    {
        $resolver = new ReportPathResolver($projectRoot, ReportStoragePolicy::fromConfig());
        $disk = $resolver->disk();
        $path = $resolver->reportPath($reportId);
        if (! $disk->exists($path)) {
            throw new InvalidArgumentException('Saved report does not exist.');
        }

        $data = $this->readJson($disk, $path);
        if ($data === null) {
            throw new InvalidArgumentException('Saved report is malformed.');
        }

        return new SavedReport($this->serializer->metadata($data, $path), $data);
    }

    public function delete(string $projectRoot, string $reportId): bool
    {
        $resolver = new ReportPathResolver($projectRoot, ReportStoragePolicy::fromConfig());
        $disk = $resolver->disk();
        $path = $resolver->reportPath($reportId);
        if (! $disk->exists($path)) {
            throw new InvalidArgumentException('Saved report does not exist.');
        }

        $disk->delete($path);

        return true;
    }

    public function clear(string $projectRoot): int
    {
        $resolver = new ReportPathResolver($projectRoot, ReportStoragePolicy::fromConfig());
        $disk = $resolver->disk();
        $directory = $resolver->reportsDirectory();
        $deleted = 0;

        foreach ($disk->files($directory) as $file) {
            if (! str_starts_with(basename($file), 'report_') || ! str_ends_with($file, '.json')) {
                continue;
            }

            $disk->delete($file);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(Filesystem $disk, string $path): ?array
    {
        try {
            $json = $disk->get($path);
        } catch (Throwable $e) {
            return null;
        }

        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }
}
