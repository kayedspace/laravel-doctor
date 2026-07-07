<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Baseline;

use kayedspace\Doctor\Domain\Baseline\BaselineReport;
use kayedspace\Doctor\Support\PathResolver;

class BaselineLoader
{
    private PathResolver $pathResolver;

    public function __construct(private readonly string $projectRoot)
    {
        $this->pathResolver = new PathResolver($projectRoot);
    }

    public function load(string $path): BaselineReport
    {
        $absolute = $this->pathResolver->resolve($path);

        if (! is_readable($absolute)) {
            throw new \RuntimeException('Baseline file is not readable');
        }

        $decoded = json_decode((string) file_get_contents($absolute), true);
        if (! is_array($decoded) || ! isset($decoded['findings']) || ! is_array($decoded['findings'])) {
            throw new \RuntimeException('Baseline file is not a recognizable Doctor report');
        }

        $fingerprints = [];
        foreach ($decoded['findings'] as $finding) {
            if (! is_array($finding) || ! isset($finding['id']) || ! is_string($finding['id'])) {
                throw new \RuntimeException('Baseline file is not a recognizable Doctor report');
            }

            $fingerprints[] = $finding['id'];
        }

        return new BaselineReport(ltrim(str_replace('\\', '/', $path), '/'), array_values(array_unique($fingerprints)));
    }
}
