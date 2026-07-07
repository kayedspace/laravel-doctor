<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

final class Wildcard
{
    /**
     * @param  string|array<int, string>|null  $patterns
     */
    public static function matchesAny(string $value, string|array|null $patterns): bool
    {
        foreach ((array) $patterns as $pattern) {
            if ($pattern === $value || fnmatch($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
