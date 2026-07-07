<?php

declare(strict_types=1);

use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Rules\RuleRegistry;
use kayedspace\Doctor\Rules\RuleSkippedException;
use kayedspace\Doctor\Rules\Static\EnvCallOutsideConfigRule;
use kayedspace\Doctor\ScanPlanResolver;
use kayedspace\Doctor\Support\Reports\ReportStore;

test('scan action aggregates findings, skipped rules and parse errors', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    // Create registry with EnvCallOutsideConfigRule and a booted rule (which will be skipped)
    $registry = new RuleRegistry([
        new EnvCallOutsideConfigRule,
        new class implements DoctorRule
        {
            public function id(): RuleId
            {
                return RuleId::SecurityGlobalModelUnguard;
            }

            public function name(): string
            {
                return 'Booted';
            }

            public function description(): string
            {
                return 'desc';
            }

            public function category(): RuleCategory
            {
                return RuleCategory::Security;
            }

            public function defaultSeverity(): Severity
            {
                return Severity::Info;
            }

            public function defaultConfidence(): Confidence
            {
                return $this->id()->defaultConfidence();
            }

            public function remediation(): string
            {
                return $this->id()->remediation();
            }

            public function examples(): array
            {
                return [];
            }

            public function capabilities(): array
            {
                return [RuleCapability::Booted];
            }

            public function isBeta(): bool
            {
                return false;
            }

            public function analyze(array $files = []): array
            {
                return [];
            }
        },
    ]);

    $action = new DoctorScanAction(new ScanPlanResolver($registry), app(ReportStore::class));

    // We target the project, but we don't exclude storage, so malformed.php parses and throws error
    $request = (new DoctorRequest($projectRoot))
        ->withExclusions(['vendor/']); // keep storage so it finds malformed.php

    $report = $action->execute($request);

    expect($report->getStatus())->toBe('completed');

    // Check findings ( UserController.php should have 1 finding )
    expect($report->getFindings())->toHaveCount(1);
    expect($report->getFindings()[0]->ruleId)->toBe(RuleId::FrameworkEnvOutsideConfig->value);

    // Check skipped rules (the extra rule requires booted capability)
    expect($report->getSkippedRules())->toHaveKey(RuleId::SecurityGlobalModelUnguard->value);

    // Check errors ( malformed.php in storage/ should cause a parse error )
    expect($report->getErrors())->toHaveCount(1);
    expect((string) $report->getErrors()[0])->toContain('Parse error in storage/malformed.php');
});

test('scan action has non-mutation safety guarantees', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    $action = new DoctorScanAction(new ScanPlanResolver(RuleRegistry::default()), app(ReportStore::class));

    $controllerPath = $projectRoot.'/app/Http/Controllers/UserController.php';
    $originalContents = file_get_contents($controllerPath);

    $request = new DoctorRequest($projectRoot);
    $report = $action->execute($request);

    // Guarantees no source modifications
    expect(file_get_contents($controllerPath))->toBe($originalContents);
});

test('scan action handles RuleSkippedException and boots correctly', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    $registry = new RuleRegistry([
        new class implements DoctorRule
        {
            public function id(): RuleId
            {
                return RuleId::SecurityGlobalModelUnguard;
            }

            public function name(): string
            {
                return 'Booted';
            }

            public function description(): string
            {
                return 'desc';
            }

            public function category(): RuleCategory
            {
                return RuleCategory::Security;
            }

            public function defaultSeverity(): Severity
            {
                return Severity::Info;
            }

            public function defaultConfidence(): Confidence
            {
                return $this->id()->defaultConfidence();
            }

            public function remediation(): string
            {
                return $this->id()->remediation();
            }

            public function examples(): array
            {
                return [];
            }

            public function capabilities(): array
            {
                return [RuleCapability::Booted];
            }

            public function isBeta(): bool
            {
                return false;
            }

            public function analyze(array $files = []): array
            {
                throw new RuleSkippedException('Skipped due to lack of environment');
            }
        },
    ]);

    $action = new DoctorScanAction(new ScanPlanResolver($registry), app(ReportStore::class));
    $request = (new DoctorRequest($projectRoot))
        ->withBootPolicy('booted');

    $report = $action->execute($request);

    expect($report->getSkippedRules())
        ->toHaveKey(RuleId::SecurityGlobalModelUnguard->value, 'Skipped due to lack of environment');
});

test('scan action uses the shared scan plan resolver output', function () {
    $projectRoot = realpath(__DIR__.'/../Fixtures/projects/safe-project');
    $request = (new DoctorRequest($projectRoot))
        ->withPaths(['app/Http/Controllers/UserController.php'])
        ->withRuntimeProbePaths(['/health']);

    $expectedPlan = app(ScanPlanResolver::class)->resolve($request);
    $report = app(DoctorScanAction::class)->execute($request);

    expect($report->getPlan()?->toArray())->toBe($expectedPlan->toArray());
});
