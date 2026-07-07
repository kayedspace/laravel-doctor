<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Baseline;

readonly class BaselineReport
{
    /**
     * @var array<string, true>
     */
    private array $fingerprintSet;

    /**
     * @param  array<int, string>  $fingerprints
     */
    public function __construct(
        public string $path,
        public array $fingerprints,
    ) {
        $this->fingerprintSet = array_fill_keys($fingerprints, true);
    }

    public function contains(string $fingerprint): bool
    {
        return isset($this->fingerprintSet[$fingerprint]);
    }
}
