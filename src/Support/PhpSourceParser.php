<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class PhpSourceParser
{
    protected Parser $parser;

    /**
     * Create a new PHP source parser instance.
     */
    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * Parse the given PHP file into a syntax tree (AST).
     *
     * @return array<int, Node>
     *
     * @throws Error If syntax parsing fails
     */
    public function parse(string $absolutePath): array
    {
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new \RuntimeException("Could not read file: {$absolutePath}");
        }

        $ast = $this->parser->parse($contents);

        return $ast ?? [];
    }
}
