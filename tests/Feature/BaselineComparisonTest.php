<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->originalDir = getcwd();
    $this->root = sys_get_temp_dir().'/doctor-baseline-command-'.bin2hex(random_bytes(6));
    mkdir($this->root.'/app', 0777, true);
    chdir($this->root);
});

afterEach(function () {
    chdir($this->originalDir);
});

test('command requires a baseline for fail on new', function () {
    $exitCode = Artisan::call('doctor:scan', ['--fail-on-new' => true]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('Baseline required');
});

test('command suppresses known baseline findings and fails on new findings', function () {
    file_put_contents($this->root.'/app/Finding.php', "<?php env('KNOWN_KEY');");

    Artisan::call('doctor:scan', ['--json' => true]);
    $first = json_decode(Artisan::output(), true);
    file_put_contents($this->root.'/doctor-baseline.json', json_encode(['findings' => $first['findings']]));

    $knownExit = Artisan::call('doctor:scan', [
        '--json' => true,
        '--baseline' => 'doctor-baseline.json',
        '--fail-on-new' => true,
    ]);
    $known = json_decode(Artisan::output(), true);

    file_put_contents($this->root.'/app/NewFinding.php', "<?php env('NEW_KEY');");
    $newExit = Artisan::call('doctor:scan', [
        '--json' => true,
        '--baseline' => 'doctor-baseline.json',
        '--fail-on-new' => true,
    ]);
    $new = json_decode(Artisan::output(), true);

    expect($knownExit)->toBe(0)
        ->and($known['findings'])->toBe([])
        ->and($newExit)->toBe(1)
        ->and(array_column($new['findings'], 'file'))->toBe(['app/NewFinding.php']);
});

test('baseline comparison is stable across line movement and never rewrites the baseline', function () {
    file_put_contents($this->root.'/app/Finding.php', "<?php\nenv('KNOWN_KEY');");

    Artisan::call('doctor:scan', ['--json' => true]);
    $first = json_decode(Artisan::output(), true);
    $baselinePath = $this->root.'/doctor-baseline.json';
    $baselineContents = json_encode(['findings' => $first['findings']]);
    file_put_contents($baselinePath, $baselineContents);

    file_put_contents($this->root.'/app/Finding.php', "<?php\n// unrelated line\n// another unrelated line\nenv('KNOWN_KEY');");

    $exitCode = Artisan::call('doctor:scan', [
        '--json' => true,
        '--baseline' => 'doctor-baseline.json',
        '--fail-on-new' => true,
    ]);
    $data = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0)
        ->and($data['findings'])->toBe([])
        ->and(file_get_contents($baselinePath))->toBe($baselineContents);
});
