<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;
use kayedspace\Doctor\Output\SarifReportFormatter;
use Opis\JsonSchema\Validator;

function sarifReportWith(DoctorFinding ...$findings): DoctorReport
{
    $report = new DoctorReport(new DoctorRequest(__DIR__));

    foreach ($findings as $finding) {
        $report->addFinding($finding);
    }

    return $report->complete();
}

function sarifFinding(
    Severity $severity = Severity::Error,
    Confidence $confidence = Confidence::High,
    ?string $file = 'app/Http/Controllers/UserController.php',
    ?int $line = 12,
): DoctorFinding {
    return new DoctorFinding(
        id: 'framework.env-outside-config.abc123',
        ruleId: RuleId::FrameworkEnvOutsideConfig->value,
        title: RuleId::FrameworkEnvOutsideConfig->findingTitle(),
        message: 'env() call outside configuration file',
        severity: $severity,
        confidence: $confidence,
        evidence: "env('API_KEY')",
        file: $file,
        line: $line,
        remediation: RuleId::FrameworkEnvOutsideConfig->remediation(),
        tags: RuleId::FrameworkEnvOutsideConfig->tags(),
    );
}

test('sarif formatter emits schema valid sarif with rule metadata and fingerprints', function () {
    $sarif = json_decode(
        (new SarifReportFormatter)->format(sarifReportWith(sarifFinding()), new OutputPolicy('sarif'))
    );
    $schema = json_decode((string) file_get_contents(__DIR__.'/../../Fixtures/sarif/sarif-schema-2.1.0.json'));

    $result = (new Validator)->validate($sarif, $schema);

    expect($result->isValid())->toBeTrue()
        ->and($sarif->version)->toBe('2.1.0')
        ->and($sarif->runs[0]->tool->driver->name)->toBe('Laravel Doctor')
        ->and($sarif->runs[0]->tool->driver->rules[0]->id)->toBe(RuleId::FrameworkEnvOutsideConfig->value)
        ->and($sarif->runs[0]->results[0]->partialFingerprints->doctorFingerprint)->toBe('framework.env-outside-config.abc123')
        ->and($sarif->runs[0]->results[0]->locations[0]->physicalLocation->artifactLocation->uri)->toBe('app/Http/Controllers/UserController.php');
});

test('sarif formatter emits valid zero finding output', function () {
    $sarif = json_decode(
        (new SarifReportFormatter)->format(sarifReportWith(), new OutputPolicy('sarif'))
    );
    $schema = json_decode((string) file_get_contents(__DIR__.'/../../Fixtures/sarif/sarif-schema-2.1.0.json'));

    expect((new Validator)->validate($sarif, $schema)->isValid())->toBeTrue()
        ->and($sarif->runs[0]->results)->toBe([]);
});

test('sarif formatter supports findings without a source location', function () {
    $sarif = json_decode(
        (new SarifReportFormatter)->format(sarifReportWith(sarifFinding(file: null, line: null)), new OutputPolicy('sarif')),
        true
    );

    expect($sarif['runs'][0]['results'][0])->not->toHaveKey('locations')
        ->and($sarif['runs'][0]['results'][0]['properties']['confidence'])->toBe('high');
});

test('sarif level comes from severity only', function (Severity $severity, string $level) {
    $sarif = json_decode(
        (new SarifReportFormatter)->format(sarifReportWith(sarifFinding(severity: $severity, confidence: Confidence::Low)), new OutputPolicy('sarif')),
        true
    );

    expect($sarif['runs'][0]['results'][0]['level'])->toBe($level)
        ->and($sarif['runs'][0]['results'][0]['properties']['confidence'])->toBe('low');
})->with([
    [Severity::Critical, 'error'],
    [Severity::Error, 'error'],
    [Severity::Warning, 'warning'],
    [Severity::Info, 'note'],
]);
