<?php

declare(strict_types=1);

use kayedspace\Doctor\Rules\Booted\MissingSecurityHeadersRule;
use kayedspace\Doctor\Rules\RuleSkippedException;
use kayedspace\Doctor\Support\Runtime\RuntimeProbeContext;

test('http probe rules throw RuleSkippedException when no probe paths configured', function () {
    RuntimeProbeContext::clear();

    $rule = new MissingSecurityHeadersRule;
    expect(fn () => $rule->analyze())->toThrow(RuleSkippedException::class);
});
