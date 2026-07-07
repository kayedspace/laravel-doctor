<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Support;

use InvalidArgumentException;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Scan\GitScope;

class DoctorRequestFactory
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromPayload(array $payload, ?string $projectRoot = null): DoctorRequest
    {
        $request = new DoctorRequest($projectRoot);

        $scopePreset = (string) ($payload['scopePreset'] ?? 'full');
        $paths = $this->stringList($payload['paths'] ?? [], 'paths');

        if ($scopePreset === 'manual') {
            $request = $paths === [] ? $request->withEmptyScope() : $request->withPaths($paths);
        } elseif ($scopePreset === 'changed') {
            $request = $request->withGitScope(GitScope::changed());
        } elseif ($scopePreset === 'laravel') {
            $request = $request->withPaths(['app', 'config', 'database', 'routes', 'resources/views']);
        } elseif ($scopePreset !== 'full') {
            throw new InvalidArgumentException('Unknown scan scope preset.');
        }

        if (($payload['rules'] ?? []) !== []) {
            $request = $request->withRule($this->stringList($payload['rules'], 'rules'));
        }

        if (($payload['packs'] ?? []) !== []) {
            $request = $request->withPack($this->stringList($payload['packs'], 'packs'));
        }

        if (($payload['exclusions'] ?? []) !== []) {
            $request = $request->withExclusions($this->stringList($payload['exclusions'], 'exclusions'));
        }

        if ($this->boolean($payload['booted'] ?? false, 'booted')) {
            $request = $request->withBootPolicy('booted');
        }

        if (($payload['probePaths'] ?? []) !== []) {
            $request = $request->withRuntimeProbePaths($this->stringList($payload['probePaths'], 'probePaths'));
        }

        if ($this->boolean($payload['auditDependencies'] ?? false, 'auditDependencies')) {
            $request = $request->withAuditDependencies();
        }

        return $request;
    }

    public function emptyReportRequest(?string $projectRoot = null): DoctorRequest
    {
        return (new DoctorRequest($projectRoot))->withEmptyScope();
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value, string $field): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("Invalid scan payload: {$field} must be a string or array of strings.");
        }

        return array_values(array_filter(array_map(
            function (mixed $item) use ($field): string {
                if (is_array($item) || is_object($item)) {
                    throw new InvalidArgumentException("Invalid scan payload: {$field} must not contain nested values.");
                }

                return trim((string) $item);
            },
            $value
        ), static fn (string $item): bool => $item !== ''));
    }

    private function boolean(mixed $value, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
                return false;
            }
        }

        throw new InvalidArgumentException("Invalid scan payload: {$field} must be a boolean value.");
    }
}
