<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class DynamicEvalVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if ($node instanceof Node\Expr\Eval_) {
            return true;
        }

        if (! $node instanceof Node\Expr\FuncCall) {
            return false;
        }

        $function = NodeExpression::lowerName($node->name);
        if ($function === null || ! $this->nameMatches($function, ['assert', 'create_function'])) {
            return false;
        }

        foreach ($node->args as $arg) {
            if (NodeExpression::isNonLiteral($arg->value)) {
                return true;
            }
        }

        return false;
    }
}
