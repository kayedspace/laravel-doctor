<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\PhpSourceParser;
use PhpParser\Error;

test('it parses valid PHP file', function () {
    $parser = new PhpSourceParser;
    $ast = $parser->parse(realpath(__DIR__.'/PathResolverTest.php'));
    expect($ast)->toBeArray();
    expect($ast)->not->toBeEmpty();
});

test('it throws on malformed PHP file', function () {
    $parser = new PhpSourceParser;
    $malformedFile = realpath(__DIR__.'/../../Fixtures/projects/safe-project/storage/malformed.php');

    expect(fn () => $parser->parse($malformedFile))
        ->toThrow(Error::class);
});
