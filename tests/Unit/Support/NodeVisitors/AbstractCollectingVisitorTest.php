<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\NodeExpression;
use kayedspace\Doctor\Support\NodeVisitors\AbstractCollectingVisitor;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class WildcardFunctionNameVisitorForTest extends AbstractCollectingVisitor
{
    /**
     * @param  array<int, string>  $patterns
     */
    public function __construct(private readonly array $patterns) {}

    protected function isMatch(Node $node): bool
    {
        $function = $node instanceof Node\Expr\FuncCall ? NodeExpression::lowerName($node->name) : null;

        return $function !== null && $this->nameMatches($function, $this->patterns);
    }
}

test('collecting visitors can match wildcard function names', function () {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $visitor = new WildcardFunctionNameVisitorForTest(['dump*']);
    $traverser = new NodeTraverser;
    $traverser->addVisitor($visitor);

    $traverser->traverse($parser->parse(<<<'PHP'
<?php

dump_custom($value);
logger($value);
PHP) ?? []);

    expect($visitor->matches)->toHaveCount(1);
});
