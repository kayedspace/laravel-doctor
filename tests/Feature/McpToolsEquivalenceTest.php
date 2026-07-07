<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use kayedspace\Doctor\Mcp\DoctorMcpServer;
use kayedspace\Doctor\Mcp\Tools\DoctorScanFilesTool;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    $this->setupFixtureProject();
    config(['app.debug' => true]);
});

afterEach(function () {
    $this->tearDownFixtureProject();
});

test('mcp scan files tool matches cli finding identity for the same request', function () {
    $response = DoctorMcpServer::tool(DoctorScanFilesTool::class, [
        'paths' => ['app/Http/Controllers/UserController.php'],
    ]);

    $ref = new ReflectionProperty($response, 'response');
    $ref->setAccessible(true);
    $jsonRpcResponse = $ref->getValue($response);
    $result = $jsonRpcResponse->toArray()['result']['structuredContent'] ?? [];

    Artisan::call('doctor:scan', [
        '--format' => 'json',
        '--path' => ['app/Http/Controllers/UserController.php'],
    ]);

    $cli = json_decode(Artisan::output(), true);

    expect($result['status'])->toBe($cli['status'])
        ->and(array_column($result['findings'], 'rule'))->toBe(array_column($cli['findings'], 'ruleId'))
        ->and(array_column($result['findings'], 'severity'))->toBe(array_column($cli['findings'], 'severity'))
        ->and($result['errors'])->toBe($cli['errors']);
});

test('mcp registry lists all built in tools', function () {
    $server = app(DoctorMcpServer::class, ['transport' => new FakeTransporter]);
    $ref = new ReflectionProperty($server, 'tools');
    $ref->setAccessible(true);
    $tools = $ref->getValue($server);

    $names = array_map(function ($toolClass) {
        return app($toolClass)->name();
    }, $tools);

    expect($names)->toContain(
        'doctor_scan',
        'doctor_scan_files',
        'doctor_scan_changed',
        'doctor_list_rules',
        'doctor_explain_rule',
        'doctor_resolve_plan',
    );
});

test('mcp tools allow-list restricts exposed tools', function () {
    config(['doctor.ai.mcp.tools' => ['doctor_scan', 'doctor_list_rules']]);

    $server = app(DoctorMcpServer::class, ['transport' => new FakeTransporter]);

    $boot = new ReflectionMethod($server, 'boot');
    $boot->setAccessible(true);
    $boot->invoke($server);

    $tools = (new ReflectionProperty($server, 'tools'));
    $tools->setAccessible(true);

    $names = array_map(fn ($toolClass) => app($toolClass)->name(), $tools->getValue($server));

    expect($names)->toEqualCanonicalizing(['doctor_scan', 'doctor_list_rules']);
});

test('empty mcp tools allow-list exposes all built in tools', function () {
    config(['doctor.ai.mcp.tools' => []]);

    $server = app(DoctorMcpServer::class, ['transport' => new FakeTransporter]);

    $boot = new ReflectionMethod($server, 'boot');
    $boot->setAccessible(true);
    $boot->invoke($server);

    $tools = (new ReflectionProperty($server, 'tools'));
    $tools->setAccessible(true);

    expect($tools->getValue($server))->toHaveCount(6);
});
