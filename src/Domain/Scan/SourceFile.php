<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Scan;

use PhpParser\Node;

class SourceFile
{
    /**
     * Create a new source file instance.
     *
     * @param  string  $path  Project-relative path
     * @param  string  $realPath  Absolute resolved path
     * @param  string  $contents  File contents
     * @param  array<int, Node>  $syntaxTree  AST nodes
     * @param  string|null  $parseError  Parser error details if parsing failed
     * @param  string  $kind  Source kind: php or blade
     */
    public function __construct(
        public string $path = '',
        public string $realPath = '',
        public string $contents = '',
        public array $syntaxTree = [],
        public ?string $parseError = null,
        public string $kind = 'php',
    ) {}

    /**
     * @param  array<int, Node>  $syntaxTree
     */
    public static function makePhp(string $path, string $realPath, string $contents, array $syntaxTree = []): self
    {
        return new self($path, $realPath, $contents, $syntaxTree, null, 'php');
    }

    public static function makeBlade(string $path, string $realPath, string $contents): self
    {
        return new self($path, $realPath, $contents, [], null, 'blade');
    }

    public static function makeFailed(string $path, string $realPath, string $contents, string $parseError): self
    {
        return new self($path, $realPath, $contents, [], $parseError, 'php');
    }

    public function isBlade(): bool
    {
        return $this->kind === 'blade' || str_ends_with($this->path, '.blade.php');
    }

    public function isPhp(): bool
    {
        return ! $this->isBlade();
    }
}
