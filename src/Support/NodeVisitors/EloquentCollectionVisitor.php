<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class EloquentCollectionVisitor extends AbstractCollectingVisitor
{
    public function __construct(private readonly string $mode) {}

    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Expr\MethodCall) {
            return false;
        }

        $method = NodeExpression::lowerName($node->name);

        if ($this->mode === 'all-filter') {
            return $method !== null
                && $this->nameMatches($method, ['where', 'filter', 'first'])
                && $node->var instanceof Node\Expr\StaticCall
                && NodeExpression::lowerName($node->var->name) === 'all';
        }

        return $this->mode === 'get-count'
            && $method === 'count'
            && $node->var instanceof Node\Expr\MethodCall
            && NodeExpression::lowerName($node->var->name) === 'get';
    }
}
