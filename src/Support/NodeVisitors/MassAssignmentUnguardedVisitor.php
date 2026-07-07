<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;

class MassAssignmentUnguardedVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Stmt\Property) {
            return false;
        }

        if (! $node->isProtected() || count($node->props) !== 1 || $node->props[0]->name->toString() !== 'guarded') {
            return false;
        }

        $default = $node->props[0]->default;

        return $default instanceof Node\Expr\Array_ && $default->items === [];
    }
}
