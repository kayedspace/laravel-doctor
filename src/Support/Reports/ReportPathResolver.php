<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Reports;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;

class ReportPathResolver
{
    public function __construct(
        string $projectRoot,
        private readonly ReportStoragePolicy $policy,
    ) {
        if (realpath($projectRoot) === false) {
            throw new \InvalidArgumentException('Project root must resolve to a real directory.');
        }
    }

    /**
     * A filesystem disk from the project's filesystems configuration, used for all report/status I/O.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->policy->disk);
    }

    public function reportsDirectory(): string
    {
        return $this->directory($this->policy->reportsPath());
    }

    public function statusDirectory(): string
    {
        return $this->directory($this->policy->statusPath());
    }

    public function reportPath(string $reportId): string
    {
        $this->assertSafeId($reportId, 'Report identifier');

        return $this->safeFilePath($this->reportsDirectory(), $reportId, 'Report path');
    }

    public function statusPath(string $scanId): string
    {
        $this->assertSafeId($scanId, 'Scan identifier');

        return $this->safeFilePath($this->statusDirectory(), $scanId, 'Scan status path');
    }

    private function directory(string $relativePath): string
    {
        if ($relativePath === '' || str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            throw new \InvalidArgumentException('Report paths must be project-relative and not contain traversal.');
        }

        $normalized = trim(str_replace('\\', '/', $relativePath), '/');

        $disk = $this->disk();
        if (! $disk->directoryExists($normalized)) {
            $disk->makeDirectory($normalized);
        }

        return $normalized;
    }

    private function safeFilePath(string $directory, string $id, string $label): string
    {
        return $directory.'/'.$id.'.json';
    }

    private function assertSafeId(string $id, string $label): void
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new \InvalidArgumentException("{$label} is invalid.");
        }
    }
}
