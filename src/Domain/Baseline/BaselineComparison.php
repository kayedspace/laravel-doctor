<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Baseline;

use kayedspace\Doctor\Domain\DoctorFinding;

readonly class BaselineComparison
{
    /**
     * @param  array<int, DoctorFinding>  $knownFindings
     * @param  array<int, DoctorFinding>  $newFindings
     */
    private function __construct(
        public array $knownFindings,
        public array $newFindings,
        public string $baselinePath,
    ) {}

    /**
     * @param  array<int, DoctorFinding>  $findings
     */
    public static function compare(array $findings, BaselineReport $baseline): self
    {
        $known = [];
        $new = [];

        foreach ($findings as $finding) {
            if ($baseline->contains($finding->id)) {
                $known[] = $finding;
            } else {
                $new[] = $finding;
            }
        }

        return new self($known, $new, $baseline->path);
    }

    public function hasNewFindings(): bool
    {
        return $this->newFindings !== [];
    }
}
