<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class UnserializeUntrustedVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        return $node instanceof Node\Expr\FuncCall
            && NodeExpression::lowerName($node->name) === 'unserialize'
            && isset($node->args[0])
            && NodeExpression::isNonLiteral($node->args[0]->value);
    }
}
