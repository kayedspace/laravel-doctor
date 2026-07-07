<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Mcp\DoctorMcpServer;
use kayedspace\Doctor\Mcp\Tools\DoctorScanFilesTool;

function structuredContent($response): array
{
    $ref = new ReflectionProperty($response, 'response');
    $ref->setAccessible(true);

    return $ref->getValue($response)->toArray()['result']['structuredContent'] ?? [];
}

beforeEach(function () {
    $this->setupFixtureProject();
    config(['app.debug' => true]);
});

afterEach(function () {
    $this->tearDownFixtureProject();
});

test('mcp tools reject hostile arguments and return structured server errors', function () {
    $response = DoctorMcpServer::tool(DoctorScanFilesTool::class, [
        'paths' => ['../.env'],
    ]);

    $response->assertHasErrors(['Invalid MCP argument']);

    $response2 = DoctorMcpServer::tool(DoctorScanFilesTool::class, [
        'paths' => ['app; rm -rf /'],
    ]);

    $response2->assertHasErrors(['Invalid MCP argument']);
});

test('disabled mcp command leaves package usable', function () {
    Config::set('doctor.ai.mcp.enabled', false);

    $response = DoctorMcpServer::tool(DoctorScanFilesTool::class, [
        'paths' => ['app/Http/Controllers/UserController.php'],
    ]);

    $ref = new ReflectionProperty($response, 'response');
    $ref->setAccessible(true);
    $jsonRpcResponse = $ref->getValue($response);
    $result = $jsonRpcResponse->toArray()['result']['structuredContent'] ?? [];

    expect($result['status'])->toBe('completed');

    $exitCode = Artisan::call('doctor:mcp');

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('MCP server is disabled');

    Artisan::call('doctor:scan', ['--format' => 'json']);
    expect(json_decode(Artisan::output(), true)['status'])->toBe('completed');
});
