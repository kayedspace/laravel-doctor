<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Tests\Support;

use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\PhpSourceParser;

class SourceFileFixture
{
    public static function forStaticRules(string $relativePath): SourceFile
    {
        $path = realpath(__DIR__.'/../Fixtures/projects/static-rules/'.$relativePath);
        $contents = file_get_contents($path);
        $isBlade = str_ends_with($relativePath, '.blade.php');
        $parser = new PhpSourceParser;

        return new SourceFile(
            path: $relativePath,
            realPath: $path,
            contents: $contents,
            syntaxTree: $isBlade ? [] : $parser->parse($path),
            kind: $isBlade ? 'blade' : 'php'
        );
    }
}
