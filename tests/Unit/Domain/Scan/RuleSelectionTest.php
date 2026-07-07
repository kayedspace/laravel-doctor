<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Scan\RuleSelection;

test('rule selection holds rule and pack choices', function () {
    $selection = new RuleSelection(
        requestedPacks: ['security'],
        requestedRules: ['rule1'],
        defaultRules: ['rule2'],
        eligibleRules: ['rule1'],
        skippedRules: ['rule2' => 'not eligible']
    );

    expect($selection->getRequestedPacks())->toBe(['security']);
    expect($selection->getRequestedRules())->toBe(['rule1']);
    expect($selection->getDefaultRules())->toBe(['rule2']);
    expect($selection->getEligibleRules())->toBe(['rule1']);
    expect($selection->getSkippedRules())->toBe(['rule2' => 'not eligible']);
});
