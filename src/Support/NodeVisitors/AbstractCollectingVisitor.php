<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\NodeVisitors;

use kayedspace\Doctor\Support\Wildcard;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

abstract class AbstractCollectingVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<int, Node>
     */
    public array $matches = [];

    /**
     * @var array<int, string>
     */
    protected array $extraPatterns = [];

    public function enterNode(Node $node)
    {
        if ($this->isMatch($node)) {
            $this->matches[] = $node;
        }

        return null;
    }

    public function addPatterns(array $patterns): static
    {
        foreach ($patterns as $pattern) {
            if (is_string($pattern)) {
                $this->extraPatterns[] = $pattern;
            }
        }

        return $this;
    }

    /**
     * @param  string|array<int, string>  $patterns
     */
    protected function nameMatches(string $name, string|array $patterns): bool
    {
        return Wildcard::matchesAny($name, $patterns)
            || ($this->extraPatterns !== [] && Wildcard::matchesAny($name, $this->extraPatterns));
    }

    abstract protected function isMatch(Node $node): bool;
}
