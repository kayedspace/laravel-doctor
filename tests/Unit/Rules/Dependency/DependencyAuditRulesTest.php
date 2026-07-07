<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Rules\Dependency\AbandonedPackageRule;
use kayedspace\Doctor\Rules\Dependency\DevInProductionRule;
use kayedspace\Doctor\Rules\Dependency\KnownVulnerabilityRule;
use kayedspace\Doctor\Rules\Dependency\ManifestHealthRule;
use kayedspace\Doctor\Rules\Dependency\OutdatedPackageRule;
use kayedspace\Doctor\Support\Composer\ComposerAuditContext;
use kayedspace\Doctor\Support\Composer\ComposerAuditRunner;

function dependencyContext(string $fixture): ComposerAuditContext
{
    $root = realpath(__DIR__.'/../../../Fixtures/projects/dependency-audit/'.$fixture);

    return (new ComposerAuditRunner($root, fakeComposerCommand(), 2))->run([
        new SourceFile(
            path: 'app/UsesDevPackage.php',
            realPath: $root.'/app/UsesDevPackage.php',
            contents: is_file($root.'/app/UsesDevPackage.php') ? (string) file_get_contents($root.'/app/UsesDevPackage.php') : '<?php',
            syntaxTree: []
        ),
    ]);
}

test('dependency rules report vulnerability abandoned outdated dev leak and manifest health findings', function () {
    ComposerAuditContext::set(dependencyContext('vulnerable'));

    try {
        $findings = [
            ...(new KnownVulnerabilityRule)->analyze(),
            ...(new AbandonedPackageRule)->analyze(),
            ...(new OutdatedPackageRule)->analyze(),
            ...(new DevInProductionRule)->analyze(),
            ...(new ManifestHealthRule)->analyze(),
        ];
    } finally {
        ComposerAuditContext::clear();
    }

    expect(array_column($findings, 'ruleId'))->toContain(
        RuleId::DependencyKnownVulnerability->value,
        RuleId::DependencyAbandonedPackage->value,
        RuleId::DependencyOutdated->value,
        RuleId::DependencyDevInProduction->value,
        RuleId::DependencyManifestHealth->value,
    );
});

test('dependency rules stay quiet for clean and ambiguous fixtures', function () {
    ComposerAuditContext::set(dependencyContext('clean'));

    try {
        $findings = [
            ...(new KnownVulnerabilityRule)->analyze(),
            ...(new AbandonedPackageRule)->analyze(),
            ...(new OutdatedPackageRule)->analyze(),
            ...(new DevInProductionRule)->analyze(),
            ...(new ManifestHealthRule)->analyze(),
        ];
    } finally {
        ComposerAuditContext::clear();
    }

    expect($findings)->toBe([]);
});
