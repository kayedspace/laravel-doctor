<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

class QueueTimeoutRetryAfterRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::QueueTimeoutRetryAfter;

    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach (Config::get('queue.connections', []) as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $timeout = $config['timeout'] ?? null;
            $retryAfter = $config['retry_after'] ?? null;

            if ($timeout !== null && $retryAfter !== null) {
                if ((int) $timeout >= (int) $retryAfter) {
                    $findings[] = $this->makeFinding(
                        message: "Queue connection '{$name}' has timeout ({$timeout}s) greater than or equal to retry_after ({$retryAfter}s). This can lead to duplicate processing.",
                        evidence: "connection: {$name}, timeout: {$timeout}, retry_after: {$retryAfter}",
                        file: 'config/queue.php'
                    );
                }
            }
        }

        return $findings;
    }
}
