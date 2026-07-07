<?php

declare(strict_types=1);

namespace kayedspace\Doctor;

use Exception;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use kayedspace\Doctor\Domain\Baseline\BaselineComparison;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Rules\RuleSkippedException;
use kayedspace\Doctor\Support\Baseline\BaselineLoader;
use kayedspace\Doctor\Support\Composer\ComposerAuditContext;
use kayedspace\Doctor\Support\Composer\ComposerAuditRunner;
use kayedspace\Doctor\Support\InlineSuppression;
use kayedspace\Doctor\Support\PathResolver;
use kayedspace\Doctor\Support\PhpFileFinder;
use kayedspace\Doctor\Support\PhpSourceParser;
use kayedspace\Doctor\Support\Reports\ReportStore;
use kayedspace\Doctor\Support\Runtime\RuntimeProbeContext;
use PhpParser\Error;
use RuntimeException;

class DoctorScanAction
{
    /**
     * Create a new scan action instance.
     */
    public function __construct(
        protected ScanPlanResolver $planResolver,
        protected ReportStore $reportStore,
    ) {}

    /**
     * Execute the scan action for the given request.
     */
    public function execute(DoctorRequest $request, ?callable $onProgress = null): DoctorReport
    {
        $report = new DoctorReport($request);

        if ($request->hasEmptyScope()) {
            $report->complete();

            return $this->finalizeReport($report);
        }

        try {
            $pathResolver = new PathResolver($request->getProjectRoot());
        } catch (InvalidArgumentException $e) {
            $report->addError($e->getMessage());
            $report->fail();

            return $this->finalizeReport($report);
        }

        try {
            $plan = $this->planResolver->resolve($request);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $report->addError($e->getMessage());
            $report->fail();

            return $this->finalizeReport($report);
        }

        $report->setPlan($plan);

        // Add skipped rules to report
        foreach ($plan->getSkippedRules() as $ruleId => $reason) {
            $report->addSkippedRule($ruleId, $reason);
        }

        // 4. Discover PHP files
        $fileFinder = new PhpFileFinder($pathResolver);
        foreach ($request->getPaths() as $path) {
            if (! file_exists($pathResolver->resolve($path))) {
                $report->addError("Path does not exist: {$path}");
            }
        }

        try {
            $relativePaths = $fileFinder->find($plan, function (string $error) use ($report) {
                $report->addError($error);
            });
        } catch (InvalidArgumentException $e) {
            $report->addError($e->getMessage());
            $report->fail();

            return $this->finalizeReport($report);
        }

        if ($onProgress) {
            $onProgress('files_found', ['count' => count($relativePaths)]);
        }

        // 5. Parse and build SourceFiles
        $parser = new PhpSourceParser;
        $sourceFiles = [];

        foreach ($relativePaths as $index => $relPath) {
            if ($onProgress) {
                $onProgress('parsing_file', ['file' => $relPath, 'current' => $index + 1, 'total' => count($relativePaths)]);
            }
            $absPath = $request->getProjectRoot().'/'.$relPath;
            $contents = @file_get_contents($absPath);
            if ($contents === false) {
                $report->addError("Could not read file: {$relPath}");

                continue;
            }

            try {
                $isBlade = str_ends_with($relPath, '.blade.php');
                $ast = $isBlade ? [] : $parser->parse($absPath);
                $sourceFiles[] = new SourceFile(
                    path: $relPath,
                    realPath: $absPath,
                    contents: $contents,
                    syntaxTree: $ast,
                    kind: $isBlade ? 'blade' : 'php'
                );
            } catch (Error $e) {
                $report->addError("Parse error in {$relPath}: ".$e->getMessage());
                $sourceFiles[] = new SourceFile(
                    path: $relPath,
                    realPath: $absPath,
                    contents: $contents,
                    syntaxTree: [],
                    parseError: $e->getMessage()
                );
            }
        }

        // Set the active runtime probe context
        RuntimeProbeContext::set(
            new RuntimeProbeContext($plan->getProbePaths(), $request->getBootPolicy())
        );
        if ($request->shouldAuditDependencies()) {
            $composerCommand = Config::get('doctor.dependency_audit.composer_command', 'composer');
            if (! is_string($composerCommand) && ! is_array($composerCommand)) {
                $composerCommand = 'composer';
            }

            $timeoutSeconds = Config::get('doctor.dependency_audit.timeout_seconds', 30);
            if (! is_int($timeoutSeconds)) {
                $timeoutSeconds = 30;
            }

            ComposerAuditContext::set(
                (new ComposerAuditRunner($request->getProjectRoot(), $composerCommand, $timeoutSeconds))->run($sourceFiles)
            );
        }

        try {
            // 6. Execute eligible rules
            $sourceFilesByPath = [];
            foreach ($sourceFiles as $sourceFile) {
                $sourceFilesByPath[$sourceFile->path] = $sourceFile;
            }
            $suppression = new InlineSuppression;

            $eligibleRules = $plan->getSelectedRules();
            foreach ($eligibleRules as $ruleIndex => $rule) {
                if ($onProgress) {
                    $onProgress('running_rule', ['rule' => $rule->id()->value, 'current' => $ruleIndex + 1, 'total' => count($eligibleRules)]);
                }
                try {
                    $findings = $rule->analyze($sourceFiles);
                    foreach ($findings as $finding) {
                        if ($finding->file !== null && isset($sourceFilesByPath[$finding->file])) {
                            $filtered = $suppression->filter($sourceFilesByPath[$finding->file], [$finding]);
                            if ($filtered === []) {
                                continue;
                            }
                        }

                        $report->addFinding($finding);
                    }
                } catch (RuleSkippedException $e) {
                    $report->addSkippedRule($rule->id()->value, $e->getMessage());
                } catch (Exception $e) {
                    $report->addError("Rule '{$rule->id()->value}' failed to analyze: ".$e->getMessage());
                }
            }
        } finally {
            RuntimeProbeContext::clear();
            ComposerAuditContext::clear();
        }

        if ($request->getBaselinePath() !== null) {
            try {
                $baseline = (new BaselineLoader($request->getProjectRoot()))->load($request->getBaselinePath());
                $comparison = BaselineComparison::compare($report->getFindings(), $baseline);
                $report->replaceFindings($comparison->newFindings);
            } catch (InvalidArgumentException|RuntimeException $e) {
                $report->addError($e->getMessage());
                $report->fail();

                return $this->finalizeReport($report);
            }
        }

        $report->complete();

        return $this->finalizeReport($report);
    }

    private function finalizeReport(DoctorReport $report): DoctorReport
    {
        $policy = ReportStoragePolicy::fromConfig();
        if (! $policy->shouldSave()) {
            return $report;
        }

        if (! $policy->enabled) {
            $report->addError('Report storage is disabled.');
            $report->fail();

            return $report;
        }

        try {
            $report->setSavedReport($this->reportStore->save($report));
        } catch (InvalidArgumentException|RuntimeException $e) {
            $report->addError('Could not save report: '.$e->getMessage());
            $report->fail();
        }

        return $report;
    }
}
