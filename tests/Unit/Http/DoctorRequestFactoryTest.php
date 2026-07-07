<?php

declare(strict_types=1);

use kayedspace\Doctor\Http\Support\DoctorRequestFactory;

test('doctor request factory builds presets and save overrides', function () {
    $factory = new DoctorRequestFactory;

    $manual = $factory->fromPayload([
        'scopePreset' => 'manual',
        'paths' => "app/Models\nroutes/web.php",
    ]);

    expect($manual->getPaths())->toBe(['app/Models', 'routes/web.php']);

    $changed = $factory->fromPayload([
        'scopePreset' => 'changed',
    ]);

    expect($changed->getGitScope()?->toArray()['mode'])->toBe('changed');

    $laravel = $factory->fromPayload(['scopePreset' => 'laravel']);

    expect($laravel->getPaths())->toBe(['app', 'config', 'database', 'routes', 'resources/views']);
});

test('manual empty scope is explicit', function () {
    $request = (new DoctorRequestFactory)->fromPayload([
        'scopePreset' => 'manual',
        'paths' => '',
    ]);

    expect($request->hasEmptyScope())->toBeTrue()
        ->and($request->getPaths())->toBe([]);
});

test('doctor request factory rejects nested lists and invalid boolean-like values', function () {
    $factory = new DoctorRequestFactory;

    expect(fn () => $factory->fromPayload([
        'scopePreset' => 'manual',
        'paths' => [['app/Models/User.php']],
    ]))->toThrow(InvalidArgumentException::class, 'Invalid scan payload: paths must not contain nested values.');
});
