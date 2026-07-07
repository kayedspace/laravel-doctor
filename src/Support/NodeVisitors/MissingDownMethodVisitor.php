<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;

class MissingDownMethodVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Stmt\Class_) {
            return false;
        }

        $hasUp = false;
        $hasDown = false;

        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === 'up' && $method->stmts !== null && $method->stmts !== []) {
                $hasUp = true;
            }

            if ($method->name->toString() === 'down' && $method->stmts !== null && $method->stmts !== []) {
                $hasDown = true;
            }
        }

        return $hasUp && ! $hasDown;
    }
}
