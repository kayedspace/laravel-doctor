<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use InvalidArgumentException;
use kayedspace\Doctor\Domain\Scan\ResolvedScanPlan;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

class PhpFileFinder
{
    protected PathResolver $pathResolver;

    /**
     * Create a new PHP file finder instance.
     */
    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * Find all eligible PHP files under the scan plan.
     *
     * @return array<int, string> Project-relative paths of found PHP files.
     */
    public function find(ResolvedScanPlan $plan, ?callable $onError = null): array
    {
        $projectRoot = $plan->getProjectRoot();
        $exclusions = $plan->getExcludedPaths();
        $fileScope = $plan->getFileScope();

        if ($fileScope->isConstrained()) {
            $included = $fileScope->paths;
            if ($included === []) {
                return [];
            }
        } else {
            $included = $plan->getIncludedPaths();
        }

        $targets = empty($included) ? [''] : $included;
        $files = [];

        foreach ($targets as $target) {
            $absTarget = $this->pathResolver->resolve($target);

            if (! file_exists($absTarget)) {
                continue;
            }

            if (is_file($absTarget)) {
                if (! $this->pathResolver->isInsideProject($absTarget)) {
                    continue;
                }

                $relative = $this->makeRelative($absTarget, $projectRoot);
                if ($this->isSupportedSource($relative) && ! $this->pathResolver->isExcluded($relative, $exclusions)) {
                    $files[] = $relative;
                }
            } elseif (is_dir($absTarget)) {
                try {
                    $directoryIterator = new RecursiveDirectoryIterator(
                        $absTarget,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    );
                } catch (UnexpectedValueException $e) {
                    if ($onError) {
                        $onError("Could not read directory {$target}: ".$e->getMessage());
                    }

                    continue;
                }

                $filter = new RecursiveCallbackFilterIterator(
                    $directoryIterator,
                    function ($current, $key, $iterator) use ($projectRoot, $exclusions) {
                        $absPath = $current->getRealPath();
                        if ($absPath === false) {
                            return false;
                        }

                        if (! $this->pathResolver->isInsideProject($absPath)) {
                            return false;
                        }

                        $relative = $this->makeRelative($absPath, $projectRoot);

                        if ($current->isDir()) {
                            return ! $this->pathResolver->isExcluded($relative.'/', $exclusions);
                        }

                        return true;
                    }
                );

                $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);

                try {
                    foreach ($iterator as $fileInfo) {
                        if (! $fileInfo->isFile()) {
                            continue;
                        }
                        $absPath = $fileInfo->getRealPath();
                        if ($absPath === false) {
                            continue;
                        }

                        if (! $this->pathResolver->isInsideProject($absPath)) {
                            continue;
                        }

                        $relative = $this->makeRelative($absPath, $projectRoot);
                        if (! $this->isSupportedSource($relative)) {
                            continue;
                        }

                        if (! $this->pathResolver->isExcluded($relative, $exclusions)) {
                            $files[] = $relative;
                        }
                    }
                } catch (UnexpectedValueException $e) {
                    if ($onError) {
                        $onError("Error iterating directory {$target}: ".$e->getMessage());
                    }
                }
            }
        }

        $files = array_unique($files);
        sort($files);

        return $files;
    }

    /**
     * Convert an absolute path to a project-relative path.
     */
    private function makeRelative(string $absolutePath, string $projectRoot): string
    {
        $projectRoot = realpath($projectRoot);
        if ($projectRoot === false || ! $this->pathResolver->isInsideProject($absolutePath)) {
            throw new InvalidArgumentException('Discovered path must stay inside the project root.');
        }

        $relative = substr($absolutePath, strlen($projectRoot));

        return ltrim(str_replace('\\', '/', $relative), '/');
    }

    private function isSupportedSource(string $relativePath): bool
    {
        return str_ends_with($relativePath, '.php') || str_ends_with($relativePath, '.blade.php');
    }
}
