<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use PhpParser\Node;

class NodeExpression
{
    public static function isLiteral(Node\Expr $expr): bool
    {
        return $expr instanceof Node\Scalar\String_
            || $expr instanceof Node\Scalar\Int_
            || $expr instanceof Node\Scalar\Float_
            || $expr instanceof Node\Expr\ConstFetch;
    }

    public static function isNonLiteral(Node\Expr $expr): bool
    {
        return ! self::isLiteral($expr);
    }

    public static function isInterpolatedOrConcat(Node\Expr $expr): bool
    {
        return $expr instanceof Node\Expr\BinaryOp\Concat
            || $expr instanceof Node\Scalar\InterpolatedString;
    }

    public static function isFalse(Node\Expr $expr): bool
    {
        return $expr instanceof Node\Expr\ConstFetch && strtolower($expr->name->toString()) === 'false';
    }

    public static function name(mixed $name): ?string
    {
        if ($name instanceof Node\Identifier || $name instanceof Node\Name) {
            return $name->toString();
        }

        return is_string($name) ? $name : null;
    }

    public static function lowerName(mixed $name): ?string
    {
        $value = self::name($name);

        return $value === null ? null : strtolower($value);
    }

    public static function isRequestInput(Node\Expr $expr): bool
    {
        if ($expr instanceof Node\Expr\FuncCall && self::lowerName($expr->name) === 'request') {
            return true;
        }

        if ($expr instanceof Node\Expr\MethodCall) {
            $method = self::lowerName($expr->name);

            if (in_array($method, ['input', 'query', 'post', 'get', 'all'], true)) {
                return true;
            }

            return self::isRequestInput($expr->var);
        }

        return false;
    }

    public static function evidence(Node $node): string
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return (self::name($node->name) ?? 'call').'(...)';
        }

        if ($node instanceof Node\Expr\MethodCall) {
            return '->'.(self::name($node->name) ?? 'call').'(...)';
        }

        if ($node instanceof Node\Expr\StaticCall) {
            return (self::name($node->class) ?? 'Class').'::'.(self::name($node->name) ?? 'call').'()';
        }

        return $node::class;
    }
}
