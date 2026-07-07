<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\App;
use kayedspace\Doctor\Domain\Enums\RuleId;

class MaintenanceModeRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::HealthMaintenanceMode;

    public function analyze(array $files = []): array
    {
        try {
            if (App::isDownForMaintenance()) {
                return [
                    $this->makeFinding(
                        message: 'The application is currently down for maintenance.',
                        evidence: 'maintenance_mode: active'
                    ),
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }
}
