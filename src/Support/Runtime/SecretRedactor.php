<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

class SecretRedactor
{
    private const SECRET_PATTERN = '/\b([a-zA-Z0-9_\-]*(?:password|passwd|secret|token|key|cert|sig|credential|private|auth|pass)[a-zA-Z0-9_\-]*)(\s*(=|:|=>)\s*)([\'"]?)([^\n\'"\s]{4,})\4/i';

    public static function redact(string $text): string
    {
        return preg_replace(self::SECRET_PATTERN, '$1$2$4[REDACTED]$4', $text);
    }

    public static function redactValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return '[REDACTED]';
        }

        return $value;
    }

    public static function redactArray(array $array): array
    {
        $redacted = [];
        $keysToRedactPattern = '/password|passwd|secret|token|key|cert|sig|credential|private|auth|pass/i';

        foreach ($array as $key => $value) {
            $stringKey = (string) $key;
            if (preg_match($keysToRedactPattern, $stringKey)) {
                $redacted[$key] = is_array($value) ? self::redactArray($value) : '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = self::redactArray($value);
            } elseif (is_string($value)) {
                $redacted[$key] = self::redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
