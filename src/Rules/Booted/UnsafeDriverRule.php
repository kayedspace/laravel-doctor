<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

class UnsafeDriverRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::ConfigUnsafeDriver;

    public function analyze(array $files = []): array
    {
        if (! App::environment('production')) {
            return [];
        }

        $findings = [];

        // Check Queue
        $queueDefault = Config::get('queue.default');
        if ($queueDefault) {
            $queueDriver = Config::get("queue.connections.{$queueDefault}.driver");
            if (strtolower((string) $queueDriver) === 'sync') {
                $findings[] = $this->makeFinding(
                    message: "Queue driver is configured as 'sync' in production. This runs jobs synchronously in the request path.",
                    evidence: 'queue_driver: sync',
                    file: 'config/queue.php',
                    index: 0
                );
            }
        }

        // Check Cache
        $cacheDefault = Config::get('cache.default');
        if ($cacheDefault) {
            $cacheDriver = Config::get("cache.stores.{$cacheDefault}.driver");
            if (strtolower((string) $cacheDriver) === 'file') {
                $findings[] = $this->makeFinding(
                    message: "Cache driver is configured as 'file' in production. Local files cannot be shared across multiple servers.",
                    evidence: 'cache_driver: file',
                    file: 'config/cache.php',
                    index: 1
                );
            }
        }

        // Check Session
        $sessionDriver = Config::get('session.driver');
        if (strtolower((string) $sessionDriver) === 'file') {
            $findings[] = $this->makeFinding(
                message: "Session driver is configured as 'file' in production. Local files cannot share session data across multiple servers.",
                evidence: 'session_driver: file',
                file: 'config/session.php',
                index: 2
            );
        }

        return $findings;
    }
}
