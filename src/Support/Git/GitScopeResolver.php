<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Git;

use kayedspace\Doctor\Domain\Scan\FileScope;
use kayedspace\Doctor\Domain\Scan\GitScope;

class GitScopeResolver
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $gitBinary = 'git',
    ) {}

    public function resolve(GitScope $scope): FileScope
    {
        $this->run(['--version'], null, 'git is not available');
        $isRepo = trim($this->run(['rev-parse', '--is-inside-work-tree'], null, 'not a git repository'));
        if ($isRepo !== 'true') {
            throw new \RuntimeException('not a git repository');
        }

        $paths = match ($scope->mode) {
            'changed' => array_merge(
                $this->names(['diff', '--name-only', '-z', 'HEAD', '--']),
                $this->names(['ls-files', '-z', '--others', '--exclude-standard'])
            ),
            'staged' => $this->names(['diff', '--cached', '--name-only', '-z', '--']),
            'base' => $this->basePaths((string) $scope->baseRef),
            default => [],
        };

        return FileScope::explicit($this->filterExistingSourceFiles($paths), 'git');
    }

    /**
     * @return array<int, string>
     */
    private function basePaths(string $baseRef): array
    {
        $mergeBase = trim($this->run(['merge-base', $baseRef, 'HEAD'], null, 'git command failed'));

        return array_merge(
            $this->names(['diff', '--name-only', '-z', $mergeBase, 'HEAD', '--']),
            $this->names(['diff', '--name-only', '-z', 'HEAD', '--']),
            $this->names(['ls-files', '-z', '--others', '--exclude-standard'])
        );
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function names(array $arguments): array
    {
        $output = $this->run($arguments, null, 'git command failed');
        if ($output === '') {
            return [];
        }

        return array_values(array_filter(explode("\0", $output), fn (string $path): bool => $path !== ''));
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function filterExistingSourceFiles(array $paths): array
    {
        $filtered = [];

        foreach ($paths as $path) {
            $path = ltrim(str_replace('\\', '/', $path), '/');
            if (! str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')) {
                continue;
            }
            if (! is_file($this->projectRoot.'/'.$path)) {
                continue;
            }

            $filtered[] = $path;
        }

        $filtered = array_values(array_unique($filtered));
        sort($filtered);

        return $filtered;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function run(array $arguments, ?string $input = null, string $errorMessage = 'git command failed'): string
    {
        $command = array_merge([$this->gitBinary], $arguments);
        $pipes = [];
        $process = @proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->projectRoot
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('git is not available');
        }

        fwrite($pipes[0], $input ?? '');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0) {
            throw new \RuntimeException($errorMessage.($stderr !== '' ? ': '.trim($stderr) : ''));
        }

        return $stdout === false ? '' : $stdout;
    }
}
