<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class RawSqlInterpolationVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            $method = NodeExpression::lowerName($node->name);

            if ($method !== null && $this->nameMatches($method, ['whereraw', 'orderbyraw', 'havingraw', 'selectraw'])) {
                return isset($node->args[0]) && NodeExpression::isInterpolatedOrConcat($node->args[0]->value);
            }
        }

        return $node instanceof Node\Expr\StaticCall
            && NodeExpression::lowerName($node->class) === 'db'
            && NodeExpression::lowerName($node->name) === 'raw'
            && isset($node->args[0])
            && NodeExpression::isInterpolatedOrConcat($node->args[0]->value);
    }
}
