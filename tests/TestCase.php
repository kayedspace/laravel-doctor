<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use kayedspace\Doctor\DoctorServiceProvider;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected string $originalBasePath = '';

    protected string $originalDir = '';

    protected string $fixtureDir = '';

    protected function setupFixtureProject(string $project = 'safe-project'): void
    {
        $this->app['env'] = 'local';
        $this->originalBasePath = $this->app->basePath();
        $this->originalDir = getcwd();
        $this->fixtureDir = $this->fixtureProjectPath($project);
        app()->setBasePath($this->fixtureDir);
        chdir($this->fixtureDir);

        config(['filesystems.disks.local.root' => storage_path('app')]);
        Storage::forgetDisk('local');

        File::deleteDirectory(storage_path('app/doctor'));
    }

    protected function tearDownFixtureProject(): void
    {
        if ($this->originalDir) {
            chdir($this->originalDir);
        }
        if ($this->originalBasePath) {
            app()->setBasePath($this->originalBasePath);
        }

        config(['filesystems.disks.local.root' => storage_path('app')]);
        Storage::forgetDisk('local');

        if ($this->fixtureDir) {
            File::deleteDirectory($this->fixtureDir.'/storage/app/doctor');
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            McpServiceProvider::class,
            DoctorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('doctor.runtime.enabled', false);
        $app['config']->set('doctor.dependency_audit.enabled', false);
    }

    protected function fixtureProjectPath(string $project = 'safe-project'): string
    {
        $path = realpath(__DIR__.'/Fixtures/projects/'.$project);

        if ($path === false) {
            throw new \RuntimeException("Missing fixture project: {$project}");
        }

        return $path;
    }

    protected function doctorReportWithFindings(?string $projectRoot = null): DoctorReport
    {
        $report = new DoctorReport(new DoctorRequest($projectRoot ?? $this->fixtureProjectPath()));

        foreach ($this->doctorFixtureFindings() as $finding) {
            $report->addFinding($finding);
        }

        return $report->complete();
    }

    /**
     * @return array<int, DoctorFinding>
     */
    protected function doctorFixtureFindings(): array
    {
        return [
            new DoctorFinding(
                id: 'fingerprint-critical',
                ruleId: 'security.command-injection',
                title: 'Command execution receives non-literal input',
                message: 'User input reaches a shell execution call.',
                severity: Severity::Critical,
                confidence: Confidence::High,
                evidence: 'exec($request->input("command"))',
                file: 'app/Http/Controllers/UserController.php',
                line: 12,
                remediation: 'Avoid shell execution or pass validated arguments through a safe process API.',
                tags: ['security', 'command-injection']
            ),
            new DoctorFinding(
                id: 'fingerprint-error',
                ruleId: 'framework.env-outside-config',
                title: 'env() call outside configuration file',
                message: 'Move this environment read into config. api_key=secret-token-123',
                severity: Severity::Error,
                confidence: Confidence::Medium,
                evidence: 'env("API_KEY", "secret-token-123")',
                file: 'app/Http/Controllers/UserController.php',
                line: 20,
                remediation: 'Move this environment variable read to a configuration file and use config() here instead.',
                tags: ['security', 'best-practice']
            ),
            new DoctorFinding(
                id: 'fingerprint-warning',
                ruleId: 'eloquent.all-then-filter',
                title: 'Eloquent all() filtered in memory',
                message: 'Move filtering into the query before loading records.',
                severity: Severity::Warning,
                confidence: Confidence::Medium,
                evidence: 'User::all()->where("active", true)',
                file: 'app/Http/Controllers/UserController.php',
                line: 33,
                remediation: 'Move filtering into the query before loading records.',
                tags: ['eloquent', 'performance']
            ),
            new DoctorFinding(
                id: 'fingerprint-info',
                ruleId: 'development.debug-function',
                title: 'Debug function call found',
                message: 'Remove debugging output before shipping.',
                severity: Severity::Info,
                confidence: Confidence::Low,
                evidence: 'dump($user)',
                remediation: 'Remove the debug call or replace it with structured logging before shipping.',
                tags: ['development', 'debug']
            ),
        ];
    }
}
