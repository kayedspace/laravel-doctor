<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
    case Critical = 'critical';

    public static function options(bool $withNone = false): array
    {
        $options = $withNone ? ['none' => 'None'] : [];
        foreach (self::cases() as $case) {
            $options[$case->value] = ucfirst($case->value);
        }

        return $options;
    }

    public function weight(): int
    {
        return match ($this) {
            self::Info => 1,
            self::Warning => 2,
            self::Error => 3,
            self::Critical => 4,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    public function isAtLeast(self $other): bool
    {
        return $this->weight() >= $other->weight();
    }
}
