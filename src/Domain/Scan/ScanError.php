<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

readonly class ScanError
{
    /**
     * Create a new scan error instance.
     */
    public function __construct(
        public string $message,
        public ?string $file = null,
        public ?int $line = null
    ) {}

    /**
     * Convert the error to a human-readable string.
     */
    public function __toString(): string
    {
        if ($this->file !== null) {
            return "{$this->message} in {$this->file}".($this->line !== null ? ":{$this->line}" : '');
        }

        return $this->message;
    }

    /**
     * Convert the error to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}
