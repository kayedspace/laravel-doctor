<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use kayedspace\Doctor\DoctorScanAction;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\GitScope;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Output\ReportFormatterResolver;
use kayedspace\Doctor\ScanPlanResolver;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class ScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctor:scan 
                            {--i|interactive : Run the scan interactively, prompting for options}
                            {--non-interactive : Skip interactive prompts and run with defaults}
                            {--list-plan : Display the analysis plan without running it}
                            {--path=* : Specific paths to analyze}
                            {--exclude=* : Paths to exclude from analysis}
                            {--rule=* : Specific rules to run}
                            {--pack=* : Specific packs of rules to run}
                            {--json : Format output as JSON}
                            {--format= : Output format: console, json, or sarif}
                            {--changed : Analyze changed and untracked source files only}
                            {--staged : Analyze staged source files only}
                            {--base= : Analyze source files changed since the merge-base with this ref}
                            {--baseline= : Project-relative JSON baseline report path}
                            {--fail-on-new : Exit non-zero when baseline comparison leaves new findings}
                            {--audit-dependencies : Opt in to Composer dependency audit checks}
                            {--no-audit : Disable Composer dependency audit checks}
                            {--fail-on-severity= : Exit command with non-zero if a finding has this or higher severity}
                            {--fail-on-confidence= : Exit command with non-zero if a finding has this or higher confidence}
                            {--booted : Opt-in to execute live, booted runtime rule analysis against the application}
                            {--no-booted : Disable live, booted runtime rule analysis}
                            {--probe-path=* : Project-relative read-only path/route to execute HTTP probes against}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a live analysis of the Laravel project state';

    /**
     * Execute the console command.
     */
    public function handle(DoctorScanAction $scanAction, ReportFormatterResolver $formatterResolver, ScanPlanResolver $planResolver): int
    {
        $format = 'console';
        $interactive = (bool) $this->option('interactive');
        $nonInteractive = (bool) $this->option('non-interactive');

        if (! $interactive && ! $nonInteractive && ! app()->runningUnitTests() && $this->input->isInteractive() && $this->isDefaultRun()) {
            $interactive = confirm(
                label: 'Would you like to configure and run the scan interactively?',
                default: true
            );
        }

        try {
            if ($interactive) {
                $responses = $this->runInteractiveWizard();
                $format = $responses['format'];

                $request = new DoctorRequest(getcwd());

                $paths = [];
                $gitScope = null;
                if ($responses['scope'] === 'base') {
                    $gitScope = GitScope::base($responses['baseRef'] ?? 'main');
                } elseif ($responses['scope'] === 'paths') {
                    $paths = array_filter(array_map('trim', explode(',', $responses['pathsInput'] ?? '')));
                } elseif ($responses['scope'] === 'changed') {
                    $gitScope = GitScope::changed();
                } elseif ($responses['scope'] === 'staged') {
                    $gitScope = GitScope::staged();
                }

                if (! empty($paths)) {
                    $request = $request->withPaths($paths);
                }

                if ($gitScope !== null) {
                    $request = $request->withGitScope($gitScope);
                }

                $exclusions = [];
                if (trim($responses['excludeInput']) !== '') {
                    $exclusions = array_filter(array_map('trim', explode(',', $responses['excludeInput'])));
                }
                if (! empty($exclusions)) {
                    $request = $request->withExclusions($exclusions);
                }

                if ($responses['rulesChoice'] === 'rules') {
                    $rules = array_filter(array_map('trim', explode(',', $responses['rulesInput'] ?? '')));
                    if (! empty($rules)) {
                        $request = $request->withRule($rules);
                    }
                } elseif ($responses['rulesChoice'] === 'packs') {
                    $packs = array_filter(array_map('trim', explode(',', $responses['packsInput'] ?? '')));
                    if (! empty($packs)) {
                        $request = $request->withPack($packs);
                    }
                }

                if ($responses['bootPolicyChoice']) {
                    $request = $request->withBootPolicy('booted');
                    $probePaths = array_filter(array_map('trim', explode(',', $responses['probeInput'] ?? '')));
                    if (! empty($probePaths)) {
                        $request = $request->withRuntimeProbePaths($probePaths);
                    }
                }

                if ($responses['auditDependencies']) {
                    $request = $request->withAuditDependencies();
                }

                $failOnSeverity = null;
                if ($responses['failSeverityChoice'] !== 'none') {
                    $failOnSeverity = Severity::tryFrom(strtolower($responses['failSeverityChoice']));
                }

                $failOnConfidence = null;
                if ($responses['failConfidenceChoice'] !== 'none') {
                    $failOnConfidence = Confidence::tryFrom(strtolower($responses['failConfidenceChoice']));
                }

                $outputPolicy = new OutputPolicy(
                    $format,
                    $failOnSeverity,
                    $failOnConfidence,
                    false,
                    null
                );
                $request = $request->withOutputPolicy($outputPolicy);
            } else {
                if ($this->option('format') !== null) {
                    $format = strtolower((string) $this->option('format'));
                } elseif ($this->option('json')) {
                    $format = 'json';
                } else {
                    $configuredFormat = Config::get('doctor.output.format', 'console');
                    if (! is_string($configuredFormat)) {
                        throw new InvalidArgumentException('doctor.output.format must be one of: console, json, sarif.');
                    }

                    $configuredFormat = strtolower($configuredFormat);
                    if (! in_array($configuredFormat, ['console', 'json', 'sarif'], true)) {
                        throw new InvalidArgumentException('doctor.output.format must be one of: console, json, sarif.');
                    }

                    $format = $configuredFormat;
                }

                $request = new DoctorRequest(getcwd());

                $paths = (array) $this->option('path');
                if (! empty($paths)) {
                    $request = $request->withPaths($paths);
                }

                $exclusions = (array) $this->option('exclude');
                if (! empty($exclusions)) {
                    $request = $request->withExclusions($exclusions);
                }

                $rules = (array) $this->option('rule');
                if (! empty($rules)) {
                    $request = $request->withRule($rules);
                }

                $packs = (array) $this->option('pack');
                if (! empty($packs)) {
                    $request = $request->withPack($packs);
                }

                $gitScopeOptions = array_filter([
                    'changed' => (bool) $this->option('changed'),
                    'staged' => (bool) $this->option('staged'),
                    'base' => $this->option('base') !== null,
                ]);
                if (count($gitScopeOptions) > 1) {
                    throw new InvalidArgumentException('Choose only one git scope option: --changed, --staged, or --base.');
                }
                if ($this->option('changed')) {
                    $request = $request->withGitScope(GitScope::changed());
                } elseif ($this->option('staged')) {
                    $request = $request->withGitScope(GitScope::staged());
                } elseif ($this->option('base') !== null) {
                    $request = $request->withGitScope(GitScope::base((string) $this->option('base')));
                }

                if ($this->option('baseline') !== null) {
                    $request = $request->withBaselinePath((string) $this->option('baseline'));
                }

                $audit = (bool) Config::get('doctor.dependency_audit.enabled', true);
                if ($this->option('audit-dependencies')) {
                    $audit = true;
                }
                if ($this->option('no-audit')) {
                    $audit = false;
                }
                if ($audit) {
                    $request = $request->withAuditDependencies();
                }

                $booted = (bool) Config::get('doctor.runtime.enabled', true);
                if ($this->option('booted')) {
                    $booted = true;
                }
                if ($this->option('no-booted')) {
                    $booted = false;
                }
                if ($booted) {
                    $request = $request->withBootPolicy('booted');
                }

                $probePaths = (array) $this->option('probe-path');
                if (! empty($probePaths)) {
                    $request = $request->withRuntimeProbePaths($probePaths);
                }

                $failOnSeverity = null;
                $failOnSeverityValue = $this->option('fail-on-severity');
                if ($failOnSeverityValue === null) {
                    $failOnSeverityValue = Config::get('doctor.fail_on.severity');
                }
                if ($failOnSeverityValue !== null) {
                    if (! is_string($failOnSeverityValue)) {
                        throw new InvalidArgumentException('doctor.fail_on.severity must be one of: error, warning, info, critical.');
                    }

                    $failOnSeverity = Severity::tryFrom(strtolower($failOnSeverityValue));
                    if ($failOnSeverity === null) {
                        throw new InvalidArgumentException('doctor.fail_on.severity must be one of: error, warning, info, critical.');
                    }
                }

                $failOnConfidence = null;
                $failOnConfidenceValue = $this->option('fail-on-confidence');
                if ($failOnConfidenceValue === null) {
                    $failOnConfidenceValue = Config::get('doctor.fail_on.confidence');
                }
                if ($failOnConfidenceValue !== null) {
                    if (! is_string($failOnConfidenceValue)) {
                        throw new InvalidArgumentException('doctor.fail_on.confidence must be one of: high, medium, low.');
                    }

                    $failOnConfidence = Confidence::tryFrom(strtolower($failOnConfidenceValue));
                    if ($failOnConfidence === null) {
                        throw new InvalidArgumentException('doctor.fail_on.confidence must be one of: high, medium, low.');
                    }
                }

                $outputPolicy = new OutputPolicy(
                    $format,
                    $failOnSeverity,
                    $failOnConfidence,
                    (bool) $this->option('fail-on-new'),
                    $request->getBaselinePath()
                );
                $request = $request->withOutputPolicy($outputPolicy);
            }
        } catch (InvalidArgumentException $e) {
            if ($format === 'json') {
                $this->line(json_encode(['error' => $e->getMessage()]));
            } else {
                $this->error('Invalid Scan Request: '.$e->getMessage());
            }

            return 1;
        }

        try {
            $plan = $planResolver->resolve($request);
        } catch (InvalidArgumentException|RuntimeException $e) {
            if ($format === 'json') {
                $this->line(json_encode(['error' => $e->getMessage()]));
            } else {
                $this->error('Plan Resolution Error: '.$e->getMessage());
            }

            return 1;
        }

        if ($this->option('list-plan')) {
            if ($format === 'json') {
                $this->line(json_encode([
                    'plan' => $plan->toArray(),
                    'bootPolicy' => $plan->getBootPolicy(),
                    'outputPolicy' => $outputPolicy->toArray(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->info('Laravel Live Doctor analysis engine starting...');
                $this->line('Analysis plan preview (rules and paths that would be run).');
                $this->line('Project Root: '.$plan->getProjectRoot());
                $this->line('Included Paths: '.implode(', ', empty($plan->getIncludedPaths()) ? ['[All Project Files]'] : $plan->getIncludedPaths()));
                $this->line('Excluded Paths: '.implode(', ', $plan->getExcludedPaths()));

                $selectedRuleIds = array_map(fn ($r) => $r->id()->value, $plan->getSelectedRules());
                $this->line('Selected Rules: '.implode(', ', $selectedRuleIds));

                if (! empty($plan->getSkippedRules())) {
                    $this->line('Skipped Rules:');
                    foreach ($plan->getSkippedRules() as $ruleId => $reason) {
                        $this->line("  - {$ruleId}: {$reason}");
                    }
                }
                $this->line('Boot Policy: '.$plan->getBootPolicy());
                if (! empty($plan->getProbePaths())) {
                    $this->line('Probe Paths: '.implode(', ', $plan->getProbePaths()));
                }
                $this->line('Output Policy Format: '.$outputPolicy->getFormat());
            }

            return 0;
        }

        if ($format === 'console') {
            $this->components->info('Laravel Live Doctor');

            $this->comment('Project Root:  '.$plan->getProjectRoot());

            $scopeLabel = 'All files';
            if ($request->getGitScope() !== null) {
                $scopeLabel = 'Git '.$request->getGitScope()->mode;
                if ($request->getGitScope()->baseRef !== null) {
                    $scopeLabel .= ' ('.$request->getGitScope()->baseRef.')';
                }
            } elseif (! empty($request->getPaths())) {
                $scopeLabel = implode(', ', $request->getPaths());
            }
            $this->comment('File Scope:    '.$scopeLabel);
            $this->comment('Boot Policy:   '.$plan->getBootPolicy());

            if (! empty($plan->getProbePaths())) {
                $this->comment('Probes:        '.implode(', ', $plan->getProbePaths()));
            }

            $caps = $plan->getAvailableCapabilities();
            $this->comment('Capabilities: '.implode(', ', $caps));
            $this->comment('Rules:        '.count($plan->getSelectedRules()).' selected'.(count($plan->getSkippedRules()) > 0 ? ' ('.count($plan->getSkippedRules()).' skipped)' : ''));
            $this->line('');
        }

        $progressCallback = null;
        if ($format === 'console') {
            $progress = null;
            $rulesProgress = null;
            $progressCallback = function (string $step, array $data) use (&$progress, &$rulesProgress) {
                if ($step === 'files_found') {
                    $this->components->info("Found {$data['count']} files to analyze.");
                    $this->line('');
                    $this->comment('Parsing files:');
                    $progress = $this->output->createProgressBar($data['count']);
                    $progress->start();
                } elseif ($step === 'parsing_file') {
                    if ($progress) {
                        $progress->advance();
                    }
                    if ($data['current'] === $data['total'] && $progress) {
                        $progress->finish();
                        $this->line('');
                        $this->line('');
                    }
                } elseif ($step === 'running_rule') {
                    if ($this->output->isVerbose()) {
                        $this->components->task("Running rule: {$data['rule']}");
                    } else {
                        if ($data['current'] === 1) {
                            $this->comment('Executing rules:');
                            $rulesProgress = $this->output->createProgressBar($data['total']);
                            $rulesProgress->start();
                        }
                        if ($rulesProgress) {
                            $rulesProgress->advance();
                        }
                        if ($data['current'] === $data['total'] && $rulesProgress) {
                            $rulesProgress->finish();
                            $this->line('');
                            $this->line('');
                        }
                    }
                }
            };
        }

        $report = $scanAction->execute($request, $progressCallback);
        $formatter = $formatterResolver->resolve($format);
        $this->line($formatter->format($report, $outputPolicy));

        if ($format === 'console' && $report->getSavedReport() !== null) {
            $saved = $report->getSavedReport();
            $this->line('Saved Report: '.$saved->reportId.($saved->path !== null ? ' ('.$saved->path.')' : ''));

            $uiPath = Config::get('doctor.ui.path', '_doctor');
            $appUrl = rtrim(Config::get('app.url', 'http://localhost'), '/');
            $this->line('Preview Link: '.$appUrl.'/'.ltrim($uiPath, '/').'/reports/'.$saved->reportId);
        }

        if ($report->getStatus() === 'failed') {
            return 1;
        }

        if ($outputPolicy->shouldFail($report)) {
            return 1;
        }

        return 0;
    }

    /**
     * Determine if this is a default scan run without filtering options.
     */
    private function isDefaultRun(): bool
    {
        return empty($this->option('path'))
            && empty($this->option('exclude'))
            && empty($this->option('rule'))
            && empty($this->option('pack'))
            && ! $this->option('changed')
            && ! $this->option('staged')
            && $this->option('base') === null
            && $this->option('baseline') === null
            && ! $this->option('audit-dependencies')
            && ! $this->option('no-audit')
            && ! $this->option('booted')
            && ! $this->option('no-booted')
            && empty($this->option('probe-path'))
            && ! $this->option('json')
            && $this->option('format') === null;
    }

    /**
     * Run the interactive CLI configuration wizard.
     *
     * @return array<string, mixed>
     */
    private function runInteractiveWizard(): array
    {
        info("
┌────────────────────────────────────────────────────────┐
|            Laravel Doctor Interactive Setup            │
└────────────────────────────────────────────────────────┘\n");

        return form()
            ->select(
                label: 'What scope of files do you want to scan?',
                options: [
                    'all' => 'All Project Files',
                    'changed' => 'Git Changed Files (uncommitted)',
                    'staged' => 'Git Staged Files',
                    'base' => 'Git Base (changes since a specific branch/ref)',
                    'paths' => 'Specific paths/directories',
                ],
                default: 'all',
                name: 'scope'
            )
            ->addIf(
                condition: fn ($responses) => $responses['scope'] === 'base',
                step: fn () => text(label: 'Enter git base reference (e.g., main or origin/main):', default: 'main'),
                name: 'baseRef'
            )
            ->addIf(
                condition: fn ($responses) => $responses['scope'] === 'paths',
                step: fn () => text(label: 'Enter paths to analyze (comma-separated, e.g., app/Http,routes):'),
                name: 'pathsInput'
            )
            ->text(
                name: 'excludeInput',
                label: 'Enter paths to exclude (optional, comma-separated, e.g., tests,database):'
            )
            ->select(
                label: 'Do you want to run specific rules or packs?',
                options: [
                    'all' => 'All eligible rules',
                    'rules' => 'Specify rules to run',
                    'packs' => 'Specify rule packs to run',
                ],
                default: 'all',
                name: 'rulesChoice'
            )
            ->addIf(
                condition: fn ($responses) => $responses['rulesChoice'] === 'rules',
                step: fn () => text(label: 'Enter specific rule IDs to run (comma-separated, e.g., framework:env-outside-config):'),
                name: 'rulesInput'
            )
            ->addIf(
                condition: fn ($responses) => $responses['rulesChoice'] === 'packs',
                step: fn () => text(label: 'Enter specific pack names to run (comma-separated, e.g., security,health):'),
                name: 'packsInput'
            )
            ->confirm(
                name: 'bootPolicyChoice',
                label: 'Execute live, booted runtime rule analysis against the application? (Config default: '.(Config::get('doctor.runtime.enabled', true) ? 'yes' : 'no').')',
                default: (bool) Config::get('doctor.runtime.enabled', true)
            )
            ->addIf(
                condition: fn ($responses) => $responses['bootPolicyChoice'] === true,
                step: fn () => text(label: 'Enter project-relative probe paths/routes (optional, comma-separated, e.g., /healthz):'),
                name: 'probeInput'
            )
            ->confirm(
                name: 'auditDependencies',
                label: 'Opt in to Composer dependency audit checks? (Config default: '.(Config::get('doctor.dependency_audit.enabled', true) ? 'yes' : 'no').')',
                default: (bool) Config::get('doctor.dependency_audit.enabled', true)
            )
            ->select(
                name: 'failSeverityChoice',
                label: 'Exit with non-zero status on minimum severity? (Config default: '.(Config::get('doctor.fail_on.severity') ?? 'none').')',
                options: Severity::options(withNone: true),
                default: Config::get('doctor.fail_on.severity') ?? 'none'
            )
            ->select(
                name: 'failConfidenceChoice',
                label: 'Exit with non-zero status on minimum confidence? (Config default: '.(Config::get('doctor.fail_on.confidence') ?? 'none').')',
                options: Confidence::options(withNone: true),
                default: Config::get('doctor.fail_on.confidence') ?? 'none'
            )
            ->select(
                name: 'format',
                label: 'Choose output format: (Config default: '.Config::get('doctor.output.format', 'console').')',
                options: [
                    'console' => 'Console',
                    'json' => 'JSON',
                    'sarif' => 'SARIF',
                ],
                default: Config::get('doctor.output.format', 'console')
            )
            ->submit();
    }
}
