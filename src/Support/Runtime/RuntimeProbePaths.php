<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

class RuntimeProbePaths
{
    /**
     * Normalize and validate runtime probe HTTP paths.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     *
     * @throws \InvalidArgumentException
     */
    public static function normalize(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            if (empty($path)) {
                throw new \InvalidArgumentException('HTTP probe path must not be empty');
            }

            if (str_contains($path, '..')) {
                throw new \InvalidArgumentException('HTTP probe path must not contain traversal');
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
                throw new \InvalidArgumentException('HTTP probe path must be a path-only value, not a full URL');
            }

            // Ensure starts with /
            $clean = '/'.ltrim($path, '/');
            // Ensure no trailing / unless it's just /
            if ($clean !== '/') {
                $clean = rtrim($clean, '/');
            }

            $normalized[] = $clean;
        }

        return $normalized;
    }
}
