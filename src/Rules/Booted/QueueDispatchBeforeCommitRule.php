<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

class QueueDispatchBeforeCommitRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::QueueDispatchBeforeCommit;

    public function analyze(array $files = []): array
    {
        $uncommittedConnections = [];

        foreach (Config::get('queue.connections', []) as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $driver = $config['driver'] ?? '';
            if ($driver === 'sync' || $driver === 'null') {
                continue;
            }

            $afterCommit = $config['after_commit'] ?? false;
            if (! $afterCommit) {
                $uncommittedConnections[] = $name;
            }
        }

        if (empty($uncommittedConnections)) {
            return [];
        }

        $namesList = implode(', ', $uncommittedConnections);

        return [
            $this->makeFinding(
                message: "The following queue connections do not have 'after_commit' enabled: {$namesList}. Jobs dispatched inside database transactions may execute on workers before the transaction commits, causing race conditions.",
                evidence: "connections: {$namesList} (after_commit: false)",
                file: 'config/queue.php'
            )->remediation("Add `'after_commit' => true` to the connection arrays in `config/queue.php`, or call `Queue::afterCommit()` in a service provider."),
        ];
    }
}
