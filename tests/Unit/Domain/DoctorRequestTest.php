<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleCategory;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\GitScope;
use kayedspace\Doctor\Domain\Scan\OutputPolicy;

test('it can be created with a project root', function () {
    // __DIR__ is a real directory
    $request = new DoctorRequest(__DIR__);
    expect($request->getProjectRoot())->toBe(__DIR__);
});

test('it validates that the project root is a real directory', function () {
    expect(fn () => new DoctorRequest('/non/existent/path/here'))
        ->toThrow(InvalidArgumentException::class, 'Project root must resolve to a real directory');
});

test('it accepts valid project-relative paths', function () {
    $request = new DoctorRequest(__DIR__);
    $request = $request->withPaths(['src/Domain', 'tests/TestCase.php']);
    expect($request->getPaths())->toBe(['src/Domain', 'tests/TestCase.php']);
});

test('it rejects paths with traversal', function () {
    $request = new DoctorRequest(__DIR__);
    expect(fn () => $request->withPaths(['../outside']))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');
});

test('it rejects absolute paths outside project root', function () {
    $request = new DoctorRequest(__DIR__);
    expect(fn () => $request->withPaths(['/etc/passwd']))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');
});

test('it rejects absolute sibling paths with the same root prefix', function () {
    $base = sys_get_temp_dir().'/doctor-request-prefix-'.bin2hex(random_bytes(4));
    $root = $base.'/project';
    $sibling = $base.'/project-sibling';

    mkdir($root, 0755, true);
    mkdir($sibling, 0755, true);
    file_put_contents($sibling.'/Outside.php', '<?php');

    try {
        $request = new DoctorRequest($root);

        expect(fn () => $request->withPaths([$sibling.'/Outside.php']))
            ->toThrow(InvalidArgumentException::class, 'Path must be project-relative');
    } finally {
        @unlink($sibling.'/Outside.php');
        @rmdir($sibling);
        @rmdir($root);
        @rmdir($base);
    }
});

test('it rejects empty rule or pack identifiers', function () {
    $request = new DoctorRequest(__DIR__);
    expect(fn () => $request->withRule(''))
        ->toThrow(InvalidArgumentException::class, 'Rule identifier must not be empty')
        ->and(fn () => $request->withPack(''))
        ->toThrow(InvalidArgumentException::class, 'Pack identifier must not be empty')
        ->and(fn () => $request->withRule(['rule1', '']))
        ->toThrow(InvalidArgumentException::class, 'Rule identifier must not be empty')
        ->and(fn () => $request->withPack(['pack1', '']))
        ->toThrow(InvalidArgumentException::class, 'Pack identifier must not be empty');

});

test('it accepts enum rule and pack identifiers', function () {
    $request = new DoctorRequest(__DIR__);
    $request = $request
        ->withRule(RuleId::FrameworkEnvOutsideConfig)
        ->withPack(RuleCategory::Framework);

    expect($request->getRules())->toBe([RuleId::FrameworkEnvOutsideConfig->value])
        ->and($request->getPacks())->toBe([RuleCategory::Framework->value]);
});

test('it holds output preferences', function () {
    $request = new DoctorRequest(__DIR__);
    $request = $request->withOutputPreferences(['format' => 'json']);
    expect($request->getOutputPreferences())->toBe(['format' => 'json']);
});

test('it holds exclusions and validates them like paths', function () {
    $request = new DoctorRequest(__DIR__);
    $request = $request->withExclusions(['vendor/', 'node_modules/']);
    expect($request->getExclusions())->toBe(['vendor/', 'node_modules/'])
        ->and(fn () => $request->withExclusions(['../outside']))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');

});

test('it holds boot policy', function () {
    $request = new DoctorRequest(__DIR__);
    expect($request->getBootPolicy())->toBe('static'); // default

    $request = $request->withBootPolicy('booted');
    expect($request->getBootPolicy())->toBe('booted');
});

test('it holds output policy', function () {
    $request = new DoctorRequest(__DIR__);
    $policy = new OutputPolicy('json');
    $request = $request->withOutputPolicy($policy);
    expect($request->getOutputPolicy())->toBe($policy);
});

test('it holds git scope, baseline path, and dependency audit state', function () {
    $request = (new DoctorRequest(__DIR__))
        ->withGitScope(GitScope::base('origin/main'))
        ->withBaselinePath('baselines/main.json')
        ->withAuditDependencies();

    expect($request->getGitScope()?->mode)->toBe('base')
        ->and($request->getGitScope()?->baseRef)->toBe('origin/main')
        ->and($request->getBaselinePath())->toBe('baselines/main.json')
        ->and($request->shouldAuditDependencies())->toBeTrue();

    expect(fn () => $request->withBaselinePath('../baseline.json'))
        ->toThrow(InvalidArgumentException::class, 'Path must be project-relative and not contain traversal');
});

test('it exports to array representation', function () {
    $policy = new OutputPolicy('json', failOnSeverity: Severity::Error);
    $request = (new DoctorRequest(__DIR__))
        ->withPaths(['src'])
        ->withExclusions(['vendor'])
        ->withRule(RuleId::FrameworkEnvOutsideConfig)
        ->withPack(RuleCategory::Framework)
        ->withOutputPolicy($policy)
        ->withOutputPreferences(['pretty' => true]);

    expect($request->toArray())->toMatchArray([
        'projectRoot' => __DIR__,
        'paths' => ['src'],
        'exclusions' => ['vendor'],
        'packs' => [RuleCategory::Framework->value],
        'rules' => [RuleId::FrameworkEnvOutsideConfig->value],
        'bootPolicy' => 'static',
        'outputPolicy' => [
            'format' => 'json',
            'failOnSeverity' => 'error',
            'failOnConfidence' => null,
            'failOnNew' => false,
            'baselinePath' => null,
        ],
        'outputPreferences' => ['pretty' => true],
    ]);
});

test('it holds runtime probe paths and validates them', function () {
    $request = new DoctorRequest(__DIR__);
    expect($request->getRuntimeProbePaths())->toBe([]);

    $request = $request->withRuntimeProbePaths(['/healthz', 'catalog']);
    // Wait, does normalization strip leading/trailing slashes or does it preserve them?
    // Let's assume we normalize to either starting with a slash or relative.
    // The contract shows: withRuntimeProbePaths(['/healthz']);
    // Let's assume they are stored as provided or normalized. Let's look at the contract:
    // "withRuntimeProbePaths() accepts an array of path-only HTTP probe paths. Invalid paths fail request construction with the same kind of validation error as invalid filesystem paths."
    // Let's make sure it holds the array.
    expect($request->getRuntimeProbePaths())->toBe(['/healthz', '/catalog']);

    // Traversal or absolute URLs
    expect(fn () => $request->withRuntimeProbePaths(['http://example.com/healthz']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $request->withRuntimeProbePaths(['/../outside']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $request->withRuntimeProbePaths(['']))
        ->toThrow(InvalidArgumentException::class);
});
