<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Reports\ReportStoragePolicy;
use kayedspace\Doctor\Rules\RuleCatalog;

class MetadataController
{
    public function rules(RuleCatalog $catalog): JsonResponse
    {
        return response()->json([
            'rules' => $catalog->all(),
        ]);
    }

    public function capabilities(): JsonResponse
    {
        $policy = ReportStoragePolicy::fromConfig();

        return response()->json([
            'scopePresets' => ['full', 'changed', 'laravel', 'manual'],
            'capabilities' => ['static', 'booted', 'dependency'],
            'reportSaving' => [
                'enabled' => $policy->enabled,
                'httpDeletionEnabled' => $policy->allowHttpDeletes,
            ],
            'formats' => ['json', 'compact-json'],
            'routes' => [
                'dashboardEnabled' => (bool) Config::get('doctor.ui.enabled', true),
                'apiEnabled' => (bool) Config::get('doctor.api.enabled', true),
            ],
        ]);
    }
}
