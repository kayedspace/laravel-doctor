<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain;

use InvalidArgumentException;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Scan\GitScope;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Support\Runtime\RuntimeProbePaths;

class DoctorRequest
{
    protected string $projectRoot;

    /**
     * @var array<int, string>
     */
    protected array $paths = [];

    /**
     * @var array<int, string>
     */
    protected array $exclusions = [];

    /**
     * @var array<int, string>
     */
    protected array $packs = [];

    /**
     * @var array<int, string>
     */
    protected array $rules = [];

    protected string $bootPolicy = 'static';

    protected ?OutputPolicy $outputPolicy = null;

    protected ?GitScope $gitScope = null;

    protected ?string $baselinePath = null;

    protected bool $auditDependencies = false;

    protected bool $emptyScope = false;

    /**
     * @var array<int, string>
     */
    protected array $runtimeProbePaths = [];

    /**
     * @var array<string, mixed>
     */
    protected array $outputPreferences = [];

    /**
     * Create a new request instance.
     */
    public function __construct(?string $projectRoot = null)
    {
        $projectRoot ??= function_exists('base_path') ? base_path() : getcwd();

        if (! $projectRoot || ! is_dir($projectRoot)) {
            throw new InvalidArgumentException('Project root must resolve to a real directory');
        }

        $realPath = realpath($projectRoot);
        if ($realPath === false) {
            throw new InvalidArgumentException('Project root must resolve to a real directory');
        }

        $this->projectRoot = $realPath;
    }

    /**
     * Create a default request instance.
     */
    public static function default(): self
    {
        return new self;
    }

    /**
     * Get the project root.
     */
    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * Specify the paths to analyze.
     *
     * @param  array<int, string>  $paths
     */
    public function withPaths(array $paths): self
    {
        $clone = clone $this;
        $clone->paths = $this->normalizePaths($paths);
        $clone->emptyScope = false;

        return $clone;
    }

    public function withEmptyScope(): self
    {
        $clone = clone $this;
        $clone->paths = [];
        $clone->emptyScope = true;

        return $clone;
    }

    /**
     * Specify the exclusions.
     *
     * @param  array<int, string>  $exclusions
     */
    public function withExclusions(array $exclusions): self
    {
        $clone = clone $this;
        $clone->exclusions = $this->normalizePaths($exclusions);

        return $clone;
    }

    /**
     * Specify the rule packs to include.
     *
     * @param  string|RuleCategory|array<int, string|RuleCategory>  $packs
     */
    public function withPack(string|RuleCategory|array $packs): self
    {
        $packsArray = array_map(
            fn (string|RuleCategory $pack): string => $pack instanceof RuleCategory ? $pack->value : $pack,
            is_array($packs) ? $packs : [$packs]
        );

        foreach ($packsArray as $pack) {
            if (empty($pack)) {
                throw new InvalidArgumentException('Pack identifier must not be empty');
            }
        }

        $clone = clone $this;
        $clone->packs = array_merge($clone->packs, $packsArray);

        return $clone;
    }

    /**
     * Specify individual rules to run.
     *
     * @param  string|RuleId|array<int, string|RuleId>  $rules
     */
    public function withRule(string|RuleId|array $rules): self
    {
        $rulesArray = array_map(
            fn (string|RuleId $rule): string => $rule instanceof RuleId ? $rule->value : $rule,
            is_array($rules) ? $rules : [$rules]
        );

        foreach ($rulesArray as $rule) {
            if (empty($rule)) {
                throw new InvalidArgumentException('Rule identifier must not be empty');
            }
        }

        $clone = clone $this;
        $clone->rules = array_merge($clone->rules, $rulesArray);

        return $clone;
    }

    /**
     * Specify output preferences.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function withOutputPreferences(array $preferences): self
    {
        $clone = clone $this;
        $clone->outputPreferences = $preferences;

        return $clone;
    }

    /**
     * Specify the boot policy.
     */
    public function withBootPolicy(string $bootPolicy): self
    {
        $clone = clone $this;
        $clone->bootPolicy = $bootPolicy;

        return $clone;
    }

    /**
     * Specify the output policy.
     */
    public function withOutputPolicy(OutputPolicy $outputPolicy): self
    {
        $clone = clone $this;
        $clone->outputPolicy = $outputPolicy;

        return $clone;
    }

    public function withGitScope(GitScope $gitScope): self
    {
        $clone = clone $this;
        $clone->gitScope = $gitScope;

        return $clone;
    }

    public function withBaselinePath(string $path): self
    {
        $clone = clone $this;
        $clone->baselinePath = $this->normalizePaths([$path])[0];

        return $clone;
    }

    public function withAuditDependencies(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->auditDependencies = $enabled;

        return $clone;
    }

    /**
     * Get the specified paths.
     *
     * @return array<int, string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get the specified exclusions.
     *
     * @return array<int, string>
     */
    public function getExclusions(): array
    {
        return $this->exclusions;
    }

    /**
     * Get the specified rule packs.
     *
     * @return array<int, string>
     */
    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * Get the specified rules.
     *
     * @return array<int, string>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get the boot policy.
     */
    public function getBootPolicy(): string
    {
        return $this->bootPolicy;
    }

    /**
     * Get the runtime probe paths.
     *
     * @return array<int, string>
     */
    public function getRuntimeProbePaths(): array
    {
        return $this->runtimeProbePaths;
    }

    /**
     * Specify the runtime probe paths.
     *
     * @param  array<int, string>  $paths
     */
    public function withRuntimeProbePaths(array $paths): self
    {
        $normalized = RuntimeProbePaths::normalize($paths);

        $clone = clone $this;
        $clone->runtimeProbePaths = $normalized;

        return $clone;
    }

    /**
     * Get the output policy.
     */
    public function getOutputPolicy(): ?OutputPolicy
    {
        return $this->outputPolicy;
    }

    /**
     * Get the output preferences.
     *
     * @return array<string, mixed>
     */
    public function getOutputPreferences(): array
    {
        return $this->outputPreferences;
    }

    public function getGitScope(): ?GitScope
    {
        return $this->gitScope;
    }

    public function getBaselinePath(): ?string
    {
        return $this->baselinePath;
    }

    public function shouldAuditDependencies(): bool
    {
        return $this->auditDependencies;
    }

    public function hasEmptyScope(): bool
    {
        return $this->emptyScope;
    }

    /**
     * Export the request to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'projectRoot' => $this->projectRoot,
            'paths' => $this->paths,
            'exclusions' => $this->exclusions,
            'packs' => $this->packs,
            'rules' => $this->rules,
            'bootPolicy' => $this->bootPolicy,
            'outputPolicy' => $this->outputPolicy?->toArray(),
            'outputPreferences' => $this->outputPreferences,
            'runtimeProbePaths' => $this->runtimeProbePaths,
            'gitScope' => $this->gitScope?->toArray(),
            'baselinePath' => $this->baselinePath,
            'auditDependencies' => $this->auditDependencies,
            'emptyScope' => $this->emptyScope,
        ];
    }

    /**
     * Normalize and validate project paths.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function normalizePaths(array $paths): array
    {
        $normalizedPaths = [];
        foreach ($paths as $path) {
            if (empty($path)) {
                throw new InvalidArgumentException('Path must not be empty');
            }
            if (str_contains($path, '..')) {
                throw new InvalidArgumentException('Path must be project-relative and not contain traversal');
            }

            // Check if absolute path
            $isAbsolute = str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path);

            if ($isAbsolute) {
                $realProjectRoot = realpath($this->projectRoot);
                if ($realProjectRoot === false || ! $this->isInsideProjectRoot($path, $realProjectRoot)) {
                    throw new InvalidArgumentException('Path must be project-relative');
                }
                // Convert to relative
                $relative = substr($path, strlen($realProjectRoot));
                $relative = ltrim($relative, '/\\');
                $normalizedPaths[] = $relative;
            } else {
                $normalizedPaths[] = ltrim($path, '/\\');
            }
        }

        return $normalizedPaths;
    }

    private function isInsideProjectRoot(string $path, string $projectRoot): bool
    {
        $root = $this->normalizePathForComparison($projectRoot);
        $candidate = $this->normalizePathForComparison($path);

        if ($root === '/') {
            return str_starts_with($candidate, '/');
        }

        return $candidate === $root || str_starts_with($candidate, $root.'/');
    }

    private function normalizePathForComparison(string $path): string
    {
        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path;

        return rtrim($path, '/') ?: '/';
    }
}
