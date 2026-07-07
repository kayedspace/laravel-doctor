<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Reports;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Domain\Reports\ScanStatus;
use kayedspace\Doctor\Domain\Reports\ScanStatusSnapshot;
use RuntimeException;

class ScanStatusStore
{
    public function create(string $projectRoot): ScanStatusSnapshot
    {
        $snapshot = new ScanStatusSnapshot(
            scanId: 'scan_'.bin2hex(random_bytes(8)),
            status: ScanStatus::Queued,
            createdAt: $this->now(),
            progressLabel: 'Queued',
        );

        $this->put($projectRoot, $snapshot);

        return $snapshot;
    }

    public function running(string $projectRoot, string $scanId): ScanStatusSnapshot
    {
        $snapshot = $this->get($projectRoot, $scanId);
        $next = new ScanStatusSnapshot(
            scanId: $scanId,
            status: ScanStatus::Running,
            createdAt: $snapshot->createdAt,
            startedAt: $this->now(),
            progressLabel: 'Running scan',
        );
        $this->put($projectRoot, $next);

        return $next;
    }

    public function complete(string $projectRoot, string $scanId, ?string $reportId): ScanStatusSnapshot
    {
        $snapshot = $this->get($projectRoot, $scanId);
        $next = new ScanStatusSnapshot(
            scanId: $scanId,
            status: ScanStatus::Completed,
            createdAt: $snapshot->createdAt,
            startedAt: $snapshot->startedAt,
            completedAt: $this->now(),
            progressLabel: 'Completed',
            reportId: $reportId,
        );
        $this->put($projectRoot, $next);

        return $next;
    }

    /**
     * @param  array<int, string>  $errors
     */
    public function fail(string $projectRoot, string $scanId, array $errors, ?string $reportId = null): ScanStatusSnapshot
    {
        $snapshot = $this->get($projectRoot, $scanId);
        $next = new ScanStatusSnapshot(
            scanId: $scanId,
            status: ScanStatus::Failed,
            createdAt: $snapshot->createdAt,
            startedAt: $snapshot->startedAt,
            completedAt: $this->now(),
            progressLabel: 'Failed',
            reportId: $reportId,
            errors: $errors,
        );
        $this->put($projectRoot, $next);

        return $next;
    }

    public function get(string $projectRoot, string $scanId): ScanStatusSnapshot
    {
        $resolver = $this->resolver($projectRoot);
        $disk = $resolver->disk();
        $path = $resolver->statusPath($scanId);
        if (! $disk->exists($path)) {
            throw new InvalidArgumentException('Scan status does not exist.');
        }

        $data = json_decode((string) $disk->get($path), true);
        if (! is_array($data)) {
            throw new InvalidArgumentException('Scan status is malformed.');
        }

        return ScanStatusSnapshot::fromArray($data);
    }

    public function put(string $projectRoot, ScanStatusSnapshot $snapshot): void
    {
        $resolver = $this->resolver($projectRoot);
        $json = json_encode($snapshot->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || ! $resolver->disk()->put($resolver->statusPath($snapshot->scanId), $json)) {
            throw new RuntimeException('Could not write scan status.');
        }
    }

    public function pruneExpired(string $projectRoot): int
    {
        $policy = ReportStoragePolicy::fromConfig();
        $resolver = new ReportPathResolver($projectRoot, $policy);
        $disk = $resolver->disk();
        $directory = $resolver->statusDirectory();
        $deleted = 0;
        $cutoff = time() - 3600;

        foreach ($disk->files($directory) as $file) {
            if (str_ends_with($file, '.json') && $disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function resolver(string $projectRoot): ReportPathResolver
    {
        return new ReportPathResolver($projectRoot, ReportStoragePolicy::fromConfig());
    }

    private function now(): string
    {
        return (new DateTimeImmutable)->format(DateTimeInterface::ATOM);
    }
}
