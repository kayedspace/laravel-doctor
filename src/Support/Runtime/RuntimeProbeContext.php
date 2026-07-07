<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

class RuntimeProbeContext
{
    protected static ?self $instance = null;

    /**
     * @param  array<int, string>  $probePaths
     */
    public function __construct(
        public readonly array $probePaths = [],
        public readonly string $bootPolicy = 'static',
    ) {}

    public static function set(self $context): void
    {
        self::$instance = $context;
    }

    public static function get(): ?self
    {
        return self::$instance;
    }

    public static function clear(): void
    {
        self::$instance = null;
    }
}
