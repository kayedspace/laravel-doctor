<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class GlobalModelUnguardVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, string>
     */
    private array $imports = [];

    /**
     * @var array<int, array{node: Node\Expr\StaticCall, class: string, baseModel: bool}>
     */
    public array $unguardCalls = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Use_) {
            $this->recordImports($node);

            return null;
        }

        if (! $node instanceof Node\Expr\StaticCall) {
            return null;
        }

        if (! $node->class instanceof Node\Name || ! $node->name instanceof Node\Identifier) {
            return null;
        }

        if ($node->name->toString() !== 'unguard') {
            return null;
        }

        $class = $this->resolveName($node->class);
        $baseModel = $class === 'Illuminate\\Database\\Eloquent\\Model';

        if (! $baseModel && ! str_starts_with($class, 'App\\Models\\')) {
            return null;
        }

        $this->unguardCalls[] = [
            'node' => $node,
            'class' => $class,
            'baseModel' => $baseModel,
        ];

        return null;
    }

    private function recordImports(Node\Stmt\Use_ $node): void
    {
        foreach ($node->uses as $use) {
            $alias = $use->alias?->toString() ?? $use->name->getLast();
            $this->imports[$alias] = $use->name->toString();
        }
    }

    private function resolveName(Node\Name $name): string
    {
        if ($name instanceof Node\Name\FullyQualified) {
            return $name->toString();
        }

        $parts = $name->getParts();
        $first = $parts[0];

        if (isset($this->imports[$first])) {
            array_shift($parts);

            return implode('\\', array_filter([$this->imports[$first], ...$parts]));
        }

        return $name->toString();
    }
}
