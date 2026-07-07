<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MigrationApplicationModelVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, string>
     */
    private array $imports = [];

    /**
     * @var array<int, array{node: Node, class: string}>
     */
    public array $references = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Use_) {
            $this->recordImports($node);

            return null;
        }

        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            $this->recordReference($node, $node->class);
        }

        return null;
    }

    private function recordImports(Node\Stmt\Use_ $node): void
    {
        foreach ($node->uses as $use) {
            $class = $use->name->toString();
            $alias = $use->alias?->toString() ?? $use->name->getLast();
            $this->imports[$alias] = $class;

            if ($this->isApplicationModel($class)) {
                $this->references[] = [
                    'node' => $use,
                    'class' => $class,
                ];
            }
        }
    }

    private function recordReference(Node $node, Node\Name $name): void
    {
        $class = $this->resolveName($name);

        if (! $this->isApplicationModel($class)) {
            return;
        }

        $this->references[] = [
            'node' => $node,
            'class' => $class,
        ];
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

    private function isApplicationModel(string $class): bool
    {
        return str_starts_with($class, 'App\\Models\\');
    }
}
