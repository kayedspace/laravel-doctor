<?php

declare(strict_types=1);

use kayedspace\Doctor\Mcp\RemediationPlanner;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\Rules\RuleRegistry;

test('remediation planner groups findings by rule and orders by severity then rule id', function () {
    $planner = new RemediationPlanner(new RuleCatalog(RuleRegistry::default()));
    $plan = $planner->plan($this->doctorReportWithFindings()->getFindings());

    expect($plan['groups'])->toHaveCount(4)
        ->and($plan['groups'][0]['rule'])->toBe('security.command-injection')
        ->and($plan['groups'][0]['severity'])->toBe('critical')
        ->and($plan['groups'][0]['locations'])->toContain('app/Http/Controllers/UserController.php:12')
        ->and($plan['groups'][0]['remediation'])->toContain('Avoid shell execution');
});

test('remediation planner is read only', function () {
    $before = glob($this->fixtureProjectPath().'/storage/doctor/**/*') ?: [];

    (new RemediationPlanner(new RuleCatalog(RuleRegistry::default())))
        ->plan($this->doctorReportWithFindings()->getFindings());

    $after = glob($this->fixtureProjectPath().'/storage/doctor/**/*') ?: [];

    expect($after)->toBe($before);
});
