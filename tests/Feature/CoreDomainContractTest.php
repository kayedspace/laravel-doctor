<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

test('artisan doctor:scan command supports list-plan', function () {
    $this->artisan('doctor:scan', ['--list-plan' => true])
        ->expectsOutput('Laravel Live Doctor analysis engine starting...')
        ->expectsOutput('Analysis plan preview (rules and paths that would be run).')
        ->assertExitCode(0);
});

test('public PHP API accepts the same scan output policy as the command surface', function () {
    $request = (new DoctorRequest(base_path()))
        ->withOutputPolicy(new OutputPolicy('json'));

    expect($request->getOutputPolicy()?->getFormat())->toBe('json')
        ->and($request->toArray()['outputPolicy']['format'])->toBe('json');
});
