<?php

declare(strict_types=1);

namespace kayedspace\Doctor;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Scan\FileScope;
use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;
use kayedspace\Doctor\Rules\RuleRegistry;
use kayedspace\Doctor\Support\Git\GitScopeResolver;
use kayedspace\Doctor\Support\Runtime\RuntimeProbePaths;

readonly class ScanPlanResolver
{
    public function __construct(
        private RuleRegistry $registry,
    ) {}

    public function resolve(DoctorRequest $request): ResolvedScanPlan
    {
        $ruleSelection = $this->registry->select($request);

        $probePaths = $request->getRuntimeProbePaths();
        if ($probePaths === []) {
            $probePaths = (array) Config::get('doctor.runtime.probe_paths', []);
        }

        $fileScope = FileScope::all();
        if ($request->getGitScope() !== null) {
            $fileScope = (new GitScopeResolver($request->getProjectRoot()))->resolve($request->getGitScope());

            if ($request->getPaths() !== []) {
                $fileScope = $this->intersectFileScopeWithPaths($fileScope, $request->getPaths());
            }
        }

        return new ResolvedScanPlan(
            projectRoot: $request->getProjectRoot(),
            includedPaths: $request->getPaths(),
            excludedPaths: array_values(array_unique(array_merge(
                (array) Config::get('doctor.exclusions', [
                    'vendor/',
                    'node_modules/',
                    'bootstrap/cache/',
                    'storage/framework/',
                    'storage/logs/',
                    '.git/',
                    '.env*',
                ]),
                $request->getExclusions(),
            ))),
            selectedRules: $ruleSelection->getEligibleRules(),
            skippedRules: $ruleSelection->getSkippedRules(),
            availableCapabilities: $this->availableCapabilities($request),
            bootPolicy: $request->getBootPolicy(),
            probePaths: RuntimeProbePaths::normalize($probePaths),
            fileScope: $fileScope,
        );
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function intersectFileScopeWithPaths(FileScope $fileScope, array $paths): FileScope
    {
        $filters = array_map(
            fn (string $path): string => rtrim(ltrim(str_replace('\\', '/', $path), '/'), '/'),
            $paths
        );

        $filtered = array_filter(
            $fileScope->paths,
            function (string $path) use ($filters): bool {
                foreach ($filters as $filter) {
                    if ($filter === '' || $path === $filter || str_starts_with($path, $filter.'/')) {
                        return true;
                    }
                }

                return false;
            }
        );

        return FileScope::explicit(array_values($filtered), $fileScope->source.'+path');
    }

    /**
     * @return array<int, string>
     */
    private function availableCapabilities(DoctorRequest $request): array
    {
        $capabilities = ['static'];

        if ($request->getBootPolicy() === 'booted') {
            $capabilities[] = 'booted';
        }

        if ($request->shouldAuditDependencies()) {
            $capabilities[] = 'dependency';
        }

        return $capabilities;
    }
}
