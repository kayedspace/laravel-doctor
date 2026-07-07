<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class RequestFlowSecurityVisitor extends AbstractCollectingVisitor
{
    public function __construct(private readonly string $mode) {}

    protected function isMatch(Node $node): bool
    {
        if ($this->mode === 'redirect') {
            $method = $node instanceof Node\Expr\MethodCall ? NodeExpression::lowerName($node->name) : null;

            return $node instanceof Node\Expr\MethodCall
                && $method !== null
                && $this->nameMatches($method, ['to', 'away'])
                && isset($node->args[0])
                && NodeExpression::isRequestInput($node->args[0]->value);
        }

        if ($this->mode === 'view') {
            return $node instanceof Node\Expr\FuncCall
                && NodeExpression::lowerName($node->name) === 'view'
                && isset($node->args[0])
                && NodeExpression::isNonLiteral($node->args[0]->value);
        }

        if ($this->mode === 'file') {
            $function = $node instanceof Node\Expr\FuncCall ? NodeExpression::lowerName($node->name) : null;

            return $node instanceof Node\Expr\FuncCall
                && $function !== null
                && $this->nameMatches($function, ['file_get_contents', 'fopen', 'readfile'])
                && isset($node->args[0])
                && NodeExpression::isRequestInput($node->args[0]->value);
        }

        $function = $node instanceof Node\Expr\FuncCall ? NodeExpression::lowerName($node->name) : null;

        return $node instanceof Node\Expr\FuncCall
            && $function !== null
            && $this->nameMatches($function, ['md5', 'sha1'])
            && isset($node->args[0])
            && $node->args[0]->value instanceof Node\Expr\Variable
            && is_string($node->args[0]->value->name)
            && preg_match('/secret|token|password|key/i', $node->args[0]->value->name) === 1;
    }
}
