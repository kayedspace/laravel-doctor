<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp;

class McpArgumentValidator
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function assertSafe(array $arguments): void
    {
        array_walk_recursive($arguments, static function (mixed $value): void {
            if (! is_string($value)) {
                return;
            }

            if (str_contains($value, '..') || str_starts_with($value, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]|[;&|`]|\\$\\(|\\r|\\n/', $value) === 1) {
                throw new \InvalidArgumentException('Invalid MCP argument: command-like and traversal values are not accepted.');
            }
        });
    }
}
