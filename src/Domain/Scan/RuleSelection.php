<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

use kayedspace\Doctor\Contracts\DoctorRule;

readonly class RuleSelection
{
    /**
     * Create a new rule selection instance.
     *
     * @param  array<int, string>  $requestedPacks
     * @param  array<int, string>  $requestedRules
     * @param  array<int, string>  $defaultRules
     * @param  array<int, DoctorRule>  $eligibleRules
     * @param  array<string, string>  $skippedRules
     */
    public function __construct(
        public array $requestedPacks = [],
        public array $requestedRules = [],
        public array $defaultRules = [],
        public array $eligibleRules = [],
        public array $skippedRules = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function getRequestedPacks(): array
    {
        return $this->requestedPacks;
    }

    /**
     * @return array<int, string>
     */
    public function getRequestedRules(): array
    {
        return $this->requestedRules;
    }

    /**
     * @return array<int, string>
     */
    public function getDefaultRules(): array
    {
        return $this->defaultRules;
    }

    /**
     * @return array<int, DoctorRule>
     */
    public function getEligibleRules(): array
    {
        return $this->eligibleRules;
    }

    /**
     * @return array<string, string>
     */
    public function getSkippedRules(): array
    {
        return $this->skippedRules;
    }
}
