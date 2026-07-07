<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;

class DebugFunctionVisitor extends AbstractCollectingVisitor
{
    private const DEBUG_FUNCTIONS = ['dd', 'dump', 'var_dump', 'print_r', 'ray', 'clock'];

    /**
     * @var array<int, Node\Expr\FuncCall>
     */
    public array $calls = [];

    protected function isMatch(Node $node): bool
    {
        if (! $node instanceof Node\Expr\FuncCall) {
            return false;
        }

        if (! $node->name instanceof Node\Name) {
            return false;
        }

        return $this->nameMatches($node->name->toLowerString(), self::DEBUG_FUNCTIONS);
    }

    public function enterNode(Node $node)
    {
        if ($this->isMatch($node) && $node instanceof Node\Expr\FuncCall) {
            $this->calls[] = $node;
        }

        parent::enterNode($node);

        return null;
    }
}
