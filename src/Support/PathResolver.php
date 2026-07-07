<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use InvalidArgumentException;

class PathResolver
{
    protected string $projectRoot;

    /**
     * Create a new path resolver instance.
     */
    public function __construct(string $projectRoot)
    {
        $realRoot = realpath($projectRoot);
        if ($realRoot === false || ! is_dir($realRoot)) {
            throw new InvalidArgumentException('Project root must resolve to a real directory');
        }

        $this->projectRoot = $realRoot;
    }

    /**
     * Resolve a path to be absolute and verify it remains strictly inside the project root.
     * Rejects path traversals and symlink escapes outside the root.
     */
    public function resolve(string $path): string
    {
        if (empty($path)) {
            return $this->projectRoot;
        }

        // Check traversal lexically
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('Path must be project-relative and not contain traversal');
        }

        // Get absolute target
        $target = $path;
        if (! str_starts_with($target, '/') && ! preg_match('/^[a-zA-Z]:[\\\\\/]/', $target)) {
            $target = $this->projectRoot.'/'.$path;
        }

        // Resolve realpath (resolves all symlinks and ..)
        $realTarget = realpath($target);
        if ($realTarget === false) {
            $normalizedTarget = $this->lexicalNormalize($target);
            if (! $this->isInsideProject($normalizedTarget)) {
                throw new InvalidArgumentException('Path must be project-relative');
            }

            return $normalizedTarget;
        }

        // Verify realTarget starts with projectRoot
        if (! $this->isInsideProject($realTarget)) {
            throw new InvalidArgumentException('Path must be project-relative');
        }

        return $realTarget;
    }

    public function isInsideProject(string $absolutePath): bool
    {
        $root = $this->normalizeForComparison($this->projectRoot);
        $path = $this->normalizeForComparison($absolutePath);

        if ($root === '/') {
            return str_starts_with($path, '/');
        }

        return $path === $root || str_starts_with($path, $root.'/');
    }

    /**
     * Check if a project-relative path is excluded.
     *
     * @param  array<int, string>  $exclusions
     */
    public function isExcluded(string $relativePath, array $exclusions): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach ($exclusions as $exclusion) {
            $exclusion = ltrim(str_replace('\\', '/', $exclusion), '/');
            if (empty($exclusion)) {
                continue;
            }

            // If exclusion ends with /, treat it as a directory prefix check
            if (str_ends_with($exclusion, '/')) {
                if (str_starts_with($relativePath, $exclusion) || $relativePath.'/' === $exclusion) {
                    return true;
                }
            } else {
                // Check exact match, or start-of-path match (if directory but without trailing slash), or glob match
                if ($relativePath === $exclusion || str_starts_with($relativePath, $exclusion.'/')) {
                    return true;
                }
                if (fnmatch($exclusion, $relativePath) || fnmatch($exclusion, basename($relativePath))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Lexically normalize path separators.
     */
    private function lexicalNormalize(string $path): string
    {
        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path;

        return rtrim($path, '/');
    }

    private function normalizeForComparison(string $path): string
    {
        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path;

        return rtrim($path, '/') ?: '/';
    }
}
