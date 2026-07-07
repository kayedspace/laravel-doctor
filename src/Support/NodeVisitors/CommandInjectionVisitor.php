<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\NodeExpression;
use PhpParser\Node;

class CommandInjectionVisitor extends AbstractCollectingVisitor
{
    protected function isMatch(Node $node): bool
    {
        if ($node instanceof Node\Expr\ShellExec) {
            return true;
        }

        if ($node instanceof Node\Expr\FuncCall) {
            $function = NodeExpression::lowerName($node->name);

            if ($function !== null && $this->nameMatches($function, ['exec', 'shell_exec', 'system', 'proc_open', 'passthru'])) {
                return isset($node->args[0]) && NodeExpression::isNonLiteral($node->args[0]->value);
            }
        }

        return $node instanceof Node\Expr\StaticCall
            && str_ends_with((string) NodeExpression::name($node->class), 'Process')
            && NodeExpression::name($node->name) === 'fromShellCommandline'
            && isset($node->args[0])
            && NodeExpression::isNonLiteral($node->args[0]->value);
    }
}
