<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console\Support;

use Illuminate\Console\Command;
use kayedspace\Doctor\Domain\Scan\GitScope;

class InteractiveWizard
{
    /**
     * Run the interactive CLI configuration wizard.
     *
     * @return array<string, mixed>
     */
    public function run(Command $command): array
    {
        $command->info('┌────────────────────────────────────────────────────────┐');
        $command->info('│            Laravel Doctor Interactive Setup            │');
        $command->info("└────────────────────────────────────────────────────────┘\n");

        $scope = $command->choice(
            'What scope of files do you want to scan?',
            [
                'all' => 'All Project Files',
                'changed' => 'Git Changed Files (uncommitted)',
                'staged' => 'Git Staged Files',
                'base' => 'Git Base (changes since a specific branch/ref)',
                'paths' => 'Specific paths/directories',
            ],
            'all'
        );

        $paths = [];
        $gitScope = null;
        if ($scope === 'base') {
            $baseRef = $command->ask('Enter git base reference (e.g., main or origin/main):', 'main');
            $gitScope = GitScope::base($baseRef);
        } elseif ($scope === 'paths') {
            $pathsInput = $command->ask('Enter paths to analyze (comma-separated, e.g., app/Http,routes):');
            $paths = array_filter(array_map('trim', explode(',', (string) $pathsInput)));
        } elseif ($scope === 'changed') {
            $gitScope = GitScope::changed();
        } elseif ($scope === 'staged') {
            $gitScope = GitScope::staged();
        }

        $exclusions = [];
        $excludeInput = $command->ask('Enter paths to exclude (optional, comma-separated, e.g., tests,database):');
        if ($excludeInput !== null && trim($excludeInput) !== '') {
            $exclusions = array_filter(array_map('trim', explode(',', $excludeInput)));
        }

        $rulesChoice = $command->choice(
            'Do you want to run specific rules or packs?',
            [
                'all' => 'All eligible rules',
                'rules' => 'Specify rules to run',
                'packs' => 'Specify rule packs to run',
            ],
            'all'
        );

        $rules = [];
        $packs = [];
        if ($rulesChoice === 'rules') {
            $rulesInput = $command->ask('Enter specific rule IDs to run (comma-separated, e.g., framework:env-outside-config):');
            if ($rulesInput !== null && trim($rulesInput) !== '') {
                $rules = array_filter(array_map('trim', explode(',', $rulesInput)));
            }
        } elseif ($rulesChoice === 'packs') {
            $packsInput = $command->ask('Enter specific pack names to run (comma-separated, e.g., security,health):');
            if ($packsInput !== null && trim($packsInput) !== '') {
                $packs = array_filter(array_map('trim', explode(',', $packsInput)));
            }
        }

        $bootPolicyChoice = $command->confirm('Execute live, booted runtime rule analysis against the application?', false);
        $bootPolicy = 'static';
        $probePaths = [];
        if ($bootPolicyChoice) {
            $bootPolicy = 'booted';
            $probeInput = $command->ask('Enter project-relative probe paths/routes (optional, comma-separated, e.g., /healthz):');
            if ($probeInput !== null && trim($probeInput) !== '') {
                $probePaths = array_filter(array_map('trim', explode(',', $probeInput)));
            }
        }

        $auditDependencies = $command->confirm('Opt in to Composer dependency audit checks?', false);

        $failSeverityChoice = $command->choice(
            'Exit with non-zero status on minimum severity?',
            [
                'none' => 'None',
                'critical' => 'Critical',
                'error' => 'Error',
                'warning' => 'Warning',
                'info' => 'Info',
            ],
            'none'
        );
        $failOnSeverity = $failSeverityChoice !== 'none' ? $failSeverityChoice : null;

        $failConfidenceChoice = $command->choice(
            'Exit with non-zero status on minimum confidence?',
            [
                'none' => 'None',
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low',
            ],
            'none'
        );
        $failOnConfidence = $failConfidenceChoice !== 'none' ? $failConfidenceChoice : null;

        $saveReportChoice = $command->choice(
            'Do you want to save this scan report?',
            [
                'default' => 'Default (based on config)',
                'yes' => 'Save report',
                'no' => 'Do not save report',
            ],
            'default'
        );
        $saveReport = $saveReportChoice === 'yes' ? true : ($saveReportChoice === 'no' ? false : null);

        $format = $command->choice(
            'Choose output format:',
            [
                'console' => 'Console (default)',
                'json' => 'JSON',
                'sarif' => 'SARIF',
            ],
            'console'
        );

        return compact(
            'paths',
            'gitScope',
            'exclusions',
            'rules',
            'packs',
            'bootPolicy',
            'probePaths',
            'auditDependencies',
            'failOnSeverity',
            'failOnConfidence',
            'saveReport',
            'format'
        );
    }
}
