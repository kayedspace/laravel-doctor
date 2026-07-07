<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Fingerprints;

class FindingFingerprint
{
    public static function make(string $ruleId, ?string $file, string $evidence, ?int $line = null, int|string $disambiguator = 0): string
    {
        if ($file !== null) {
            if (str_starts_with($file, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $file) === 1) {
                throw new \InvalidArgumentException('Path must be project-relative');
            }

            if (str_contains($file, '..')) {
                throw new \InvalidArgumentException('Path must be project-relative and not contain traversal');
            }
        }

        $parts = [
            $ruleId,
            $file === null ? '' : ltrim(str_replace('\\', '/', $file), '/'),
            self::normalizeEvidence($evidence),
            (string) $disambiguator,
        ];

        return $ruleId.'.'.hash('sha256', implode("\n", $parts));
    }

    private static function normalizeEvidence(string $evidence): string
    {
        $evidence = trim($evidence);

        return preg_replace('/\s+/', ' ', $evidence) ?? $evidence;
    }
}
