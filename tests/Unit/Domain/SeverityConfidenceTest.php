<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;

test('severity weights and ordering helpers', function () {
    expect(Severity::Info->weight())->toBe(1)
        ->and(Severity::Warning->weight())->toBe(2)
        ->and(Severity::Error->weight())->toBe(3)
        ->and(Severity::Critical->weight())->toBe(4)
        ->and(Severity::Critical->isHigherThan(Severity::Error))->toBeTrue()
        ->and(Severity::Error->isHigherThan(Severity::Warning))->toBeTrue()
        ->and(Severity::Warning->isHigherThan(Severity::Info))->toBeTrue()
        ->and(Severity::Info->isHigherThan(Severity::Warning))->toBeFalse()
        ->and(Severity::Error->isAtLeast(Severity::Error))->toBeTrue()
        ->and(Severity::Error->isAtLeast(Severity::Warning))->toBeTrue()
        ->and(Severity::Warning->isAtLeast(Severity::Error))->toBeFalse();

});

test('confidence weights, ordering and confirmed vs advisory helpers', function () {
    expect(Confidence::Low->weight())->toBe(1)
        ->and(Confidence::Medium->weight())->toBe(2)
        ->and(Confidence::High->weight())->toBe(3)
        ->and(Confidence::High->isHigherThan(Confidence::Medium))->toBeTrue()
        ->and(Confidence::Medium->isHigherThan(Confidence::Low))->toBeTrue()
        ->and(Confidence::Low->isHigherThan(Confidence::Medium))->toBeFalse()
        ->and(Confidence::High->isConfirmed())->toBeTrue()
        ->and(Confidence::Medium->isConfirmed())->toBeTrue()
        ->and(Confidence::Low->isConfirmed())->toBeFalse()
        ->and(Confidence::Low->isAdvisory())->toBeTrue()
        ->and(Confidence::Medium->isAdvisory())->toBeFalse()
        ->and(Confidence::High->isAdvisory())->toBeFalse();

    // High and Medium are confirmed

    // Low is advisory
});
