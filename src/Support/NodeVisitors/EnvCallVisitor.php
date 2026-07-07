<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;

class EnvCallVisitor extends AbstractCollectingVisitor
{
    /**
     * @var array<int, Node\Expr\FuncCall>
     */
    public array $envCalls = [];

    protected function isMatch(Node $node): bool
    {
        return $node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && $node->name->toLowerString() === 'env';
    }

    public function enterNode(Node $node)
    {
        if ($this->isMatch($node) && $node instanceof Node\Expr\FuncCall) {
            $this->envCalls[] = $node;
        }

        parent::enterNode($node);

        return null;
    }
}
