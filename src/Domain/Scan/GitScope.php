<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

readonly class GitScope
{
    private function __construct(
        public string $mode,
        public ?string $baseRef = null,
        public bool $includeUntracked = false,
    ) {}

    public static function changed(): self
    {
        return new self('changed', includeUntracked: true);
    }

    public static function staged(): self
    {
        return new self('staged');
    }

    public static function base(string $ref): self
    {
        if ($ref === '') {
            throw new \InvalidArgumentException('Base ref must not be empty');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $ref) === 1) {
            throw new \InvalidArgumentException('Base ref must not contain control characters');
        }

        return new self('base', $ref, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'baseRef' => $this->baseRef,
            'includeUntracked' => $this->includeUntracked,
        ];
    }
}
