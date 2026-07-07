<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class SslVerificationDisabledVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Expr\ArrayItem || $node->key === null || ! NodeExpression::isFalse($node->value)) {
            return false;
        }

        if ($node->key instanceof Node\Scalar\String_ && $node->key->value === 'verify') {
            return true;
        }

        return $node->key instanceof Node\Expr\ConstFetch
            && $node->key->name->toString() === 'CURLOPT_SSL_VERIFYPEER';
    }
}
