<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class ConfigCallInConfigFileVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        $function = $node instanceof Node\Expr\FuncCall ? NodeExpression::lowerName($node->name) : null;

        return $node instanceof Node\Expr\FuncCall
            && $function !== null
            && $this->nameMatches($function, ['config', 'app']);
    }
}
