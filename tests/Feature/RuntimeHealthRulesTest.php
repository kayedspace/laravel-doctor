<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\MaintenanceMode;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleId;
use Spatie\Health\ResultStores\ResultStore;

test('health rules are skipped when not booted', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    $request = new DoctorRequest($projectRoot);

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    expect($report->getSkippedRules())->toHaveKey(RuleId::HealthDatabaseUnreachable->value);
});

test('health pack runs and reports maintenance mode finding when booted is enabled', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    $mockMaintenance = Mockery::mock(MaintenanceMode::class);
    $mockMaintenance->shouldReceive('active')->andReturn(true);
    $mockMaintenance->shouldReceive('data')->andReturn([]);
    app()->instance(MaintenanceMode::class, $mockMaintenance);

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted')
        ->withPack('health');

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    expect($report->getFindings())->not->toBeEmpty();

    $ruleIds = array_map(fn ($f) => $f->ruleId, $report->getFindings());
    expect($ruleIds)->toContain(RuleId::HealthMaintenanceMode->value);

    app()->offsetUnset(MaintenanceMode::class);
});

test('health pack runs and reports database unreachable when spatie reports failed database check', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    if (! class_exists('Spatie\Health\ResultStores\ResultStore')) {
        class SpatieHealthResultStoreMockFeature
        {
            /** @var callable */
            public $latestResultsCallback;

            public function latestResults()
            {
                return ($this->latestResultsCallback)();
            }
        }
        class_alias(SpatieHealthResultStoreMockFeature::class, 'Spatie\Health\ResultStores\ResultStore');
    }

    $resultStore = new ResultStore;
    $resultStore->latestResultsCallback = function () {
        $dbCheckResult = new stdClass;
        $dbCheckResult->name = 'Database';
        $dbCheckResult->status = 'failed';

        $results = new stdClass;
        $results->checkResults = [$dbCheckResult];

        return $results;
    };
    app()->instance('Spatie\Health\ResultStores\ResultStore', $resultStore);

    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted')
        ->withRule('health.database-unreachable');

    $action = app(DoctorScanAction::class);
    $report = $action->execute($request);

    $findings = $report->getFindings();
    expect($findings)->toHaveCount(1)
        ->and($findings[0]->ruleId)->toBe(RuleId::HealthDatabaseUnreachable->value);

    app()->offsetUnset('Spatie\Health\ResultStores\ResultStore');
});
