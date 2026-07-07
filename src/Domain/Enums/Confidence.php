<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

enum Confidence: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

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
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    public function isConfirmed(): bool
    {
        return $this === self::High || $this === self::Medium;
    }

    public function isAdvisory(): bool
    {
        return $this === self::Low;
    }
}
