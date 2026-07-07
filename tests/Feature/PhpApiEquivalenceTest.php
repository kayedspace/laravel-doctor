<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Facades\Doctor;

function publicApiEquivalenceRows(DoctorReport $report): array
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

function publicApiCliRows(array $data): array
{
    return array_map(
        fn (array $finding): array => [
            'id' => $finding['id'],
            'ruleId' => $finding['ruleId'],
            'severity' => $finding['severity'],
            'file' => $finding['file'],
            'line' => $finding['line'],
        ],
        $data['findings']
    );
}

beforeEach(function () {
    $this->originalDir = getcwd();
    $this->fixtureDir = realpath(__DIR__.'/../Fixtures/projects/safe-project');

    chdir($this->fixtureDir);
});

afterEach(function () {
    chdir($this->originalDir);
});

test('public php api matches json cli findings for the same request', function () {
    $paths = ['app/Http/Controllers/UserController.php'];

    $exitCode = Artisan::call('doctor:scan', [
        '--path' => $paths,
        '--json' => true,
    ]);
    $cliData = json_decode(Artisan::output(), true);

    $apiReport = Doctor::scan(
        (new DoctorRequest($this->fixtureDir))->withPaths($paths)
    );

    expect($exitCode)->toBe(0)
        ->and($cliData)->toBeArray()
        ->and($apiReport->getStatus())->toBe($cliData['status'])
        ->and(publicApiEquivalenceRows($apiReport))->toBe(publicApiCliRows($cliData));
});
