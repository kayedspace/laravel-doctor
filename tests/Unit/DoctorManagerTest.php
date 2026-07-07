<?php

declare(strict_types=1);

use kayedspace\Doctor\DoctorManager;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Facades\Doctor;

function publicApiFixtureRoot(): string
{
    $root = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    expect($root)->toBeString();

    return $root;
}

function publicApiFindingRows(DoctorReport $report): array
{
    return array_map(
        fn ($finding): array => [
            'id' => $finding->id,
            'ruleId' => $finding->ruleId,
            'severity' => $finding->severity->value,
            'file' => $finding->file,
            'line' => $finding->line,
        ],
        $report->getFindings()
    );
}

test('facade resolves the container-bound manager singleton', function () {
    expect(app(DoctorManager::class))->toBe(app(DoctorManager::class))
        ->and(Doctor::getFacadeRoot())->toBe(app(DoctorManager::class));
});

test('scan delegates to the container resolved scan action', function () {
    $request = (new DoctorRequest(publicApiFixtureRoot()))
        ->withPaths(['app/Http/Controllers/UserController.php']);

    $apiReport = Doctor::scan($request);
    $actionReport = app(DoctorScanAction::class)->execute($request);

    expect($apiReport->getStatus())->toBe($actionReport->getStatus())
        ->and(publicApiFindingRows($apiReport))->toBe(publicApiFindingRows($actionReport));
});

test('scan returns report errors for an invalid project root', function () {
    $request = new DoctorRequest(publicApiFixtureRoot());
    $property = new ReflectionProperty($request, 'projectRoot');
    $property->setValue($request, publicApiFixtureRoot().'/missing-root');

    $report = Doctor::scan($request);

    expect($report->getStatus())->toBe('failed')
        ->and($report->getErrors())->not->toBeEmpty();
});

test('scan exposes summary findings errors and status', function () {
    $report = Doctor::scan(
        (new DoctorRequest(publicApiFixtureRoot()))
            ->withPaths(['app/Http/Controllers/UserController.php'])
    );

    expect($report->getStatus())->toBe('completed')
        ->and($report->getFindings())->toHaveCount(1)
        ->and($report->getErrors())->toBeEmpty()
        ->and($report->getSummary()->error)->toBe(1);
});

test('scan carries no hidden state between repeated calls', function () {
    $request = (new DoctorRequest(publicApiFixtureRoot()))
        ->withPaths(['app/Http/Controllers/UserController.php']);

    $first = Doctor::scan($request);
    $second = Doctor::scan($request);

    expect($second->getStatus())->toBe($first->getStatus())
        ->and(publicApiFindingRows($second))->toBe(publicApiFindingRows($first));
});

test('files scans the given paths with default rules', function () {
    app()->setBasePath(publicApiFixtureRoot());

    $report = Doctor::files([
        'app/Http/Controllers/UserController.php',
        'config/app.php',
    ]);

    expect($report->getStatus())->toBe('completed')
        ->and($report->getPlan()?->getIncludedPaths())->toBe([
            'app/Http/Controllers/UserController.php',
            'config/app.php',
        ])
        ->and(array_column(publicApiFindingRows($report), 'file'))->toBe([
            'app/Http/Controllers/UserController.php',
        ]);
});

test('files treats an empty list as an empty completed report', function () {
    app()->setBasePath(publicApiFixtureRoot());

    $report = Doctor::files([]);

    expect($report->getStatus())->toBe('completed')
        ->and($report->hasFindings())->toBeFalse()
        ->and($report->getFindings())->toBeEmpty()
        ->and($report->getErrors())->toBeEmpty()
        ->and($report->getPlan())->toBeNull();
});

test('files reports missing paths while scanning valid siblings', function () {
    app()->setBasePath(publicApiFixtureRoot());

    $report = Doctor::files([
        'app/Http/Controllers/UserController.php',
        'missing.php',
    ]);

    expect(publicApiFindingRows($report))->toHaveCount(1)
        ->and((string) $report->getErrors()[0])->toContain('missing.php');
});
