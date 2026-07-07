<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

readonly class FileScope
{
    /**
     * @param  array<int, string>  $paths
     */
    private function __construct(
        public array $paths,
        public string $source,
        private bool $constrained,
    ) {}

    public static function all(): self
    {
        return new self([], 'all', false);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public static function explicit(array $paths, string $source = 'path'): self
    {
        $normalized = [];

        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            if (str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1) {
                throw new \InvalidArgumentException('Path must be project-relative');
            }

            if (str_contains($path, '..')) {
                throw new \InvalidArgumentException('Path must be project-relative and not contain traversal');
            }

            $normalized[] = ltrim(str_replace('\\', '/', $path), '/');
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return new self($normalized, $source, true);
    }

    public function isConstrained(): bool
    {
        return $this->constrained;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paths' => $this->paths,
            'source' => $this->source,
            'isConstrained' => $this->constrained,
        ];
    }
}
