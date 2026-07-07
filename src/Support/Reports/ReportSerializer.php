<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Reports;

use Composer\InstalledVersions;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\Reports\SavedReportMetadata;

class ReportSerializer
{
    public const SCHEMA_VERSION = '1';

    /**
     * @return array<string, mixed>
     */
    public function serialize(DoctorReport $report, string $reportId, DateTimeImmutable $createdAt): array
    {
        $request = $report->getRequest();
        $requestData = $this->redactedRequest($report);
        $planData = $this->redactedPlan($report);

        $projectRoot = $request->getProjectRoot();

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'reportId' => $reportId,
            'createdAt' => $createdAt->format(DateTimeInterface::ATOM),
            'packageVersion' => $this->packageVersion(),
            'project' => [
                'name' => basename($projectRoot),
                'rootHash' => 'sha256:'.hash('sha256', $projectRoot),
            ],
            'request' => $requestData,
            'plan' => $planData,
            'status' => $report->getStatus(),
            'summary' => $report->getSummary()->toArray(),
            'findings' => array_map(fn ($finding): array => $finding->toArray(), $report->getFindings()),
            'skippedRules' => $report->getSkippedRules(),
            'errors' => array_map(fn ($error): array => $error->toArray(), $report->getErrors()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicReportArray(DoctorReport $report): array
    {
        $data = $report->toArray();
        $data['request'] = $this->redactedRequest($report);
        $data['plan'] = $this->redactedPlan($report);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function metadata(array $data, ?string $path = null): SavedReportMetadata
    {
        $this->assertValid($data);

        return new SavedReportMetadata(
            reportId: (string) $data['reportId'],
            createdAt: (string) $data['createdAt'],
            status: (string) $data['status'],
            summary: $data['summary'],
            scopeLabel: $this->scopeLabel($data),
            schemaVersion: (string) $data['schemaVersion'],
            path: $path,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assertValid(array $data): void
    {
        foreach (['schemaVersion', 'reportId', 'createdAt', 'status', 'summary', 'findings', 'errors'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Saved report is missing {$key}.");
            }
        }

        if ($data['schemaVersion'] !== self::SCHEMA_VERSION) {
            throw new InvalidArgumentException('Saved report schema version is not supported.');
        }

        if (! is_array($data['summary']) || ! is_array($data['findings']) || ! is_array($data['errors'])) {
            throw new InvalidArgumentException('Saved report has an invalid shape.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function scopeLabel(array $data): string
    {
        $request = is_array($data['request'] ?? null) ? $data['request'] : [];
        $paths = is_array($request['paths'] ?? null) ? $request['paths'] : [];
        $gitScope = $request['gitScope'] ?? null;

        if (is_array($gitScope) && isset($gitScope['mode'])) {
            return 'Git '.$gitScope['mode'];
        }

        if ($paths === []) {
            return 'Full project';
        }

        return count($paths).' path'.(count($paths) === 1 ? '' : 's');
    }

    private function packageVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            return InstalledVersions::getPrettyVersion('kayedspace/laravel-doctor') ?? 'dev';
        }

        return 'dev';
    }

    /**
     * @return array<string, mixed>
     */
    private function redactedRequest(DoctorReport $report): array
    {
        $requestData = $report->getRequest()->toArray();
        unset($requestData['projectRoot']);

        return $requestData;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function redactedPlan(DoctorReport $report): ?array
    {
        $planData = $report->getPlan()?->toArray();
        if ($planData === null) {
            return null;
        }

        unset($planData['projectRoot']);

        return $planData;
    }
}
