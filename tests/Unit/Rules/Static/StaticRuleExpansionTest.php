<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Rules\Static\AllThenFilterRule;
use kayedspace\Doctor\Rules\Static\BladeUnescapedOutputRule;
use kayedspace\Doctor\Rules\Static\CommandInjectionRule;
use kayedspace\Doctor\Rules\Static\ConfigCallInConfigFileRule;
use kayedspace\Doctor\Rules\Static\CountViaCollectionRule;
use kayedspace\Doctor\Rules\Static\CsrfExceptWildcardRule;
use kayedspace\Doctor\Rules\Static\DebugModeEnabledRule;
use kayedspace\Doctor\Rules\Static\DynamicEvalRule;
use kayedspace\Doctor\Rules\Static\DynamicViewPathRule;
use kayedspace\Doctor\Rules\Static\FillableForeignKeyRule;
use kayedspace\Doctor\Rules\Static\InsecureSessionConfigRule;
use kayedspace\Doctor\Rules\Static\MassAssignmentUnguardedRule;
use kayedspace\Doctor\Rules\Static\MissingDownMethodRule;
use kayedspace\Doctor\Rules\Static\PathTraversalFileAccessRule;
use kayedspace\Doctor\Rules\Static\RawSqlInterpolationRule;
use kayedspace\Doctor\Rules\Static\SslVerificationDisabledRule;
use kayedspace\Doctor\Rules\Static\UnencryptedCookieRule;
use kayedspace\Doctor\Rules\Static\UnserializeUntrustedRule;
use kayedspace\Doctor\Rules\Static\UnvalidatedRedirectRule;
use kayedspace\Doctor\Rules\Static\VerboseDebugLoggingRule;
use kayedspace\Doctor\Rules\Static\WeakHashAlgorithmRule;
use kayedspace\Doctor\Rules\Static\WeakHashingRoundsRule;
use kayedspace\Doctor\Tests\Support\SourceFileFixture;
use PhpParser\ParserFactory;

beforeEach(function () {
    Config::set('doctor.targets', []);
});

function expansionSourceFileFromCode(string $relativePath, string $contents): SourceFile
{
    $parser = (new ParserFactory)->createForNewestSupportedVersion();

    return new SourceFile(
        path: $relativePath,
        realPath: __FILE__,
        contents: $contents,
        syntaxTree: $parser->parse($contents) ?? []
    );
}

test('security rules detect representative risky patterns', function () {
    $cases = [
        [new RawSqlInterpolationRule, 'app/Http/Controllers/RawSqlInterpolationController.php', RuleId::SecurityRawSqlInterpolation, Severity::Critical, 3],
        [new MassAssignmentUnguardedRule, 'app/Models/UnguardedModel.php', RuleId::SecurityMassAssignmentUnguarded, Severity::Error, 1],
        [new CommandInjectionRule, 'app/Http/Controllers/CommandInjectionController.php', RuleId::SecurityCommandInjection, Severity::Critical, 3],
        [new DynamicEvalRule, 'app/Http/Controllers/DynamicEvalController.php', RuleId::SecurityDynamicEval, Severity::Critical, 3],
        [new UnserializeUntrustedRule, 'app/Http/Controllers/UnserializeController.php', RuleId::SecurityUnserializeUntrusted, Severity::Error, 1],
        [new SslVerificationDisabledRule, 'app/Http/Controllers/SslVerificationController.php', RuleId::SecuritySslVerificationDisabled, Severity::Error, 2],
        [new CsrfExceptWildcardRule, 'app/Http/Middleware/VerifyCsrfToken.php', RuleId::SecurityCsrfExceptWildcard, Severity::Warning, 1],
        [new FillableForeignKeyRule, 'app/Models/FillableForeignKeyModel.php', RuleId::SecurityFillableForeignKey, Severity::Warning, 1],
        [new UnencryptedCookieRule, 'app/Http/Middleware/EncryptCookies.php', RuleId::SecurityUnencryptedCookie, Severity::Warning, 1],
    ];

    foreach ($cases as [$rule, $file, $ruleId, $severity, $count]) {
        $findings = $rule->analyze([SourceFileFixture::forStaticRules($file)]);

        expect($findings)->toHaveCount($count);
        expect($findings[0]->ruleId)->toBe($ruleId->value);
        expect($findings[0]->severity)->toBe($severity);
        expect($findings[0]->line)->toBeGreaterThan(0);
    }
});

test('config and framework rules detect representative risky patterns', function () {
    $cases = [
        [new InsecureSessionConfigRule, 'config/session.php', RuleId::SecurityInsecureSessionConfig, 3],
        [new DebugModeEnabledRule, 'config/app.php', RuleId::SecurityDebugModeEnabled, 1],
        [new WeakHashingRoundsRule, 'config/hashing.php', RuleId::SecurityWeakHashingRounds, 1],
        [new VerboseDebugLoggingRule, 'config/logging.php', RuleId::SecurityVerboseDebugLogging, 1],
        [new ConfigCallInConfigFileRule, 'config/runtime_calls.php', RuleId::FrameworkConfigCallInConfigFile, 2],
    ];

    foreach ($cases as [$rule, $file, $ruleId, $count]) {
        $findings = $rule->analyze([SourceFileFixture::forStaticRules($file)]);

        expect($findings)->toHaveCount($count);
        expect($findings[0]->ruleId)->toBe($ruleId->value);
    }
});

test('beta rules are advisory and detect representative patterns', function () {
    $cases = [
        [new UnvalidatedRedirectRule, 'app/Http/Controllers/RequestFlowSecurityController.php', RuleId::SecurityUnvalidatedRedirect, 1],
        [new DynamicViewPathRule, 'app/Http/Controllers/RequestFlowSecurityController.php', RuleId::SecurityDynamicViewPath, 1],
        [new PathTraversalFileAccessRule, 'app/Http/Controllers/RequestFlowSecurityController.php', RuleId::SecurityPathTraversalFileAccess, 1],
        [new WeakHashAlgorithmRule, 'app/Http/Controllers/RequestFlowSecurityController.php', RuleId::SecurityWeakHashAlgorithm, 1],
        [new BladeUnescapedOutputRule, 'resources/views/unescaped-output.blade.php', RuleId::SecurityBladeUnescapedOutput, 1],
        [new AllThenFilterRule, 'app/Http/Controllers/AllThenFilterController.php', RuleId::EloquentAllThenFilter, 2],
        [new CountViaCollectionRule, 'app/Http/Controllers/CountViaCollectionController.php', RuleId::EloquentCountViaCollection, 1],
    ];

    foreach ($cases as [$rule, $file, $ruleId, $count]) {
        $findings = $rule->analyze([SourceFileFixture::forStaticRules($file)]);

        expect($rule->isBeta())->toBeTrue();
        expect($findings)->toHaveCount($count);
        expect($findings[0]->ruleId)->toBe($ruleId->value);
    }
});

test('migration missing down method rule detects non-reversible migrations', function () {
    $findings = (new MissingDownMethodRule)->analyze([
        SourceFileFixture::forStaticRules('database/migrations/2026_01_01_000004_missing_down_method.php'),
    ]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->ruleId)->toBe(RuleId::MigrationMissingDownMethod->value);
});

test('path glob scoped rules skip non-matching paths', function () {
    $matchingFile = SourceFileFixture::forStaticRules('app/Models/FillableForeignKeyModel.php');
    $nonMatchingFile = new SourceFile(
        path: 'app/Http/Controllers/FillableForeignKeyModel.php',
        realPath: $matchingFile->realPath,
        contents: $matchingFile->contents,
        syntaxTree: $matchingFile->syntaxTree,
    );

    $findings = (new FillableForeignKeyRule)->analyze([$matchingFile, $nonMatchingFile]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->file)->toBe('app/Models/FillableForeignKeyModel.php');
});

test('configured visitor targets add custom wildcard patterns', function () {
    Config::set('doctor.targets.security.command-injection', ['danger_*']);

    $findings = (new CommandInjectionRule)->analyze([
        expansionSourceFileFromCode('app/Http/Controllers/CustomTargetController.php', <<<'PHP'
<?php

function risky(string $command): void
{
    danger_shell($command);
    danger_shell('literal');
}
PHP),
    ]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->evidence)->toBe('danger_shell(...)');
});
