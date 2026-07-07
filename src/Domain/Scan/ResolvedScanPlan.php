<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

use kayedspace\Doctor\Contracts\DoctorRule;

class ResolvedScanPlan
{
    /**
     * Create a new resolved scan plan instance.
     *
     * @param  array<int, string>  $includedPaths
     * @param  array<int, string>  $excludedPaths
     * @param  array<int, DoctorRule>  $selectedRules
     * @param  array<string, string>  $skippedRules
     * @param  array<int, string>  $availableCapabilities
     * @param  array<int, string>  $probePaths
     */
    public function __construct(
        public string $projectRoot = '',
        public array $includedPaths = [],
        public array $excludedPaths = [],
        public array $selectedRules = [],
        public array $skippedRules = [],
        public array $availableCapabilities = [],
        public string $bootPolicy = 'static',
        public array $probePaths = [],
        public ?FileScope $fileScope = null,
    ) {}

    public static function make(string $projectRoot): self
    {
        return new self(projectRoot: $projectRoot);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function include(array $paths): self
    {
        $this->includedPaths = $paths;

        return $this;
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function exclude(array $paths): self
    {
        $this->excludedPaths = $paths;

        return $this;
    }

    /**
     * @param  array<int, DoctorRule>  $rules
     */
    public function withRules(array $rules): self
    {
        $this->selectedRules = $rules;

        return $this;
    }

    /**
     * @param  array<string, string>  $skippedRules
     */
    public function withSkippedRules(array $skippedRules): self
    {
        $this->skippedRules = $skippedRules;

        return $this;
    }

    /**
     * @param  array<int, string>  $capabilities
     */
    public function withCapabilities(array $capabilities): self
    {
        $this->availableCapabilities = $capabilities;

        return $this;
    }

    public function withBootPolicy(string $bootPolicy): self
    {
        $this->bootPolicy = $bootPolicy;

        return $this;
    }

    /**
     * @param  array<int, string>  $probePaths
     */
    public function withProbePaths(array $probePaths): self
    {
        $this->probePaths = $probePaths;

        return $this;
    }

    public function withFileScope(?FileScope $fileScope): self
    {
        $this->fileScope = $fileScope;

        return $this;
    }

    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * @return array<int, string>
     */
    public function getIncludedPaths(): array
    {
        return $this->includedPaths;
    }

    /**
     * @return array<int, string>
     */
    public function getExcludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * @return array<int, DoctorRule>
     */
    public function getSelectedRules(): array
    {
        return $this->selectedRules;
    }

    /**
     * @return array<string, string>
     */
    public function getSkippedRules(): array
    {
        return $this->skippedRules;
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableCapabilities(): array
    {
        return $this->availableCapabilities;
    }

    public function getBootPolicy(): string
    {
        return $this->bootPolicy;
    }

    /**
     * @return array<int, string>
     */
    public function getProbePaths(): array
    {
        return $this->probePaths;
    }

    public function getFileScope(): FileScope
    {
        return $this->fileScope ?? FileScope::all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'projectRoot' => $this->projectRoot,
            'includedPaths' => $this->includedPaths,
            'excludedPaths' => $this->excludedPaths,
            'selectedRules' => array_map(fn ($r) => $r->id()->value, $this->selectedRules),
            'skippedRules' => $this->skippedRules,
            'availableCapabilities' => $this->availableCapabilities,
            'bootPolicy' => $this->bootPolicy,
            'probePaths' => $this->probePaths,
            'fileScope' => $this->getFileScope()->toArray(),
        ];
    }
}
