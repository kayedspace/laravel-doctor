<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;

class FillableForeignKeyVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Stmt\Property || count($node->props) !== 1 || $node->props[0]->name->toString() !== 'fillable') {
            return false;
        }

        $default = $node->props[0]->default;
        if (! $default instanceof Node\Expr\Array_) {
            return false;
        }

        foreach ($default->items as $item) {
            if ($item->value instanceof Node\Scalar\String_ && str_ends_with($item->value->value, '_id')) {
                return true;
            }
        }

        return false;
    }
}
