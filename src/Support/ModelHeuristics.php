<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use PhpParser\Node;

class ModelHeuristics
{
    public static function isApplicationModelName(string $name): bool
    {
        return str_starts_with(ltrim($name, '\\'), 'App\\Models\\');
    }

    /**
     * @param  array<string, string>  $uses
     */
    public static function isApplicationModelNode(Node\Name|string $name, array $uses = []): bool
    {
        $raw = $name instanceof Node\Name ? $name->toString() : $name;
        $resolved = $uses[$raw] ?? $raw;

        return self::isApplicationModelName($resolved);
    }

    /**
     * @param  array<string, string>  $uses
     */
    public static function isEloquentModelName(string $name, array $uses = []): bool
    {
        $name = ltrim($name, '\\');

        return $name === 'Illuminate\\Database\\Eloquent\\Model'
            || ($name === 'Model' && ($uses['Model'] ?? null) === 'Illuminate\\Database\\Eloquent\\Model');
    }
}
