<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Composer;

use kayedspace\Doctor\Domain\Scan\SourceFile;

class ComposerAuditContext
{
    private static ?self $instance = null;

    /**
     * @param  array<string, mixed>  $composerJson
     * @param  array<string, mixed>  $composerLock
     * @param  array<string, mixed>  $auditOutput
     * @param  array<string, mixed>  $outdatedOutput
     * @param  array<string, mixed>  $validateOutput
     * @param  array<int, SourceFile>  $sourceFiles
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public readonly string $projectRoot,
        public readonly array $composerJson = [],
        public readonly array $composerLock = [],
        public readonly array $auditOutput = [],
        public readonly array $outdatedOutput = [],
        public readonly array $validateOutput = [],
        public readonly array $sourceFiles = [],
        public readonly array $errors = [],
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

    public function hasErrorContaining(string $needle): bool
    {
        foreach ($this->errors as $error) {
            if (str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }
}
