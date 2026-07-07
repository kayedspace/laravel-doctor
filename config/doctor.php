<?php

/**
 * Laravel Doctor configuration.
 *
 * This file controls default scan exclusions, rule selection, severity
 * overrides, visitor target patterns, CLI output policy, failure thresholds,
 * and opt-in booted runtime checks. Path patterns and rule-id keys support
 * glob wildcards: *, ?, and [..].
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    |
    | Project-relative path glob patterns skipped by scans. Values are strings
    | matched with fnmatch; use trailing slashes for directories.
    |
    */
    'exclusions' => [
        'vendor/',
        'node_modules/',
        'bootstrap/cache/',
        'storage/framework/',
        'storage/logs/',
        '.git/',
        '.env*',
        // 'app/Legacy/*',
        // 'storage/app/private/*.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Enablement
    |--------------------------------------------------------------------------
    |
    | A map of rule IDs or rule-id glob patterns to booleans. Set a rule or
    | pattern to false to disable it; wildcard keys such as security.* are
    | allowed.
    |
    */
    'rules' => [
        // 'security.*' => false,
        // 'framework.env-outside-config' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Packs Selection
    |--------------------------------------------------------------------------
    |
    | A list of rule packs to run. If defined, only rules belonging to these
    | categories will run by default. Valid values are 'security', 'framework',
    | 'migration', 'performance', 'development', 'health'.
    |
    */
    'packs' => [
        // 'security', 'performance'
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity Overrides
    |--------------------------------------------------------------------------
    |
    | A map of rule IDs or rule-id glob patterns to severity strings. Valid
    | values are: 'error', 'warning', 'info', and 'critical'.
    |
    */
    'severities' => [
        // 'security.*' => 'error',
        // 'migration.*' => 'warning',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Targets
    |--------------------------------------------------------------------------
    |
    | A map of rule IDs to arrays of extra name patterns. Values are glob
    | patterns matched against the identifiers that rule's visitor searches,
    | such as function names, method names, or class names.
    |
    */
    'targets' => [
        // 'development.debug-function' => ['ray', 'log*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Defaults for command output when no CLI output flag is passed.
    |
    */
    'output' => [
        /*
         * Default command output format. Accepted values are 'console',
         * 'json', and 'sarif'. An explicit --format CLI value overrides this
         * setting and the legacy --json alias.
         */
        // 'format' => 'json',
        'format' => 'console',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard UI
    |--------------------------------------------------------------------------
    |
    | The read-only /_doctor browser dashboard. It is always protected: open in
    | the local environment, and elsewhere it requires a `viewDoctor` Gate that
    | your app defines (undefined = denied). The middleware stack below runs in
    | addition to that gate, so add 'auth' (or your own) to require a login.
    |
    */
    'ui' => [
        // Register the /_doctor routes at all. Disable to remove the surface entirely.
        'enabled' => env('DOCTOR_UI_ENABLED', true),

        // URI prefix the dashboard is served from.
        'path' => env('DOCTOR_UI_PATH', '_doctor'),

        // Middleware applied to the dashboard routes (the viewDoctor gate runs on top).
        'middleware' => ['web'],

        // Gate checked outside local environments. Undefined gates deny access.
        'gate' => 'viewDoctor',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API
    |--------------------------------------------------------------------------
    |
    | Machine-readable scan, metadata, and saved-report endpoints. Disabled
    | routes are not registered.
    |
    */
    'api' => [
        // Register the /_doctor/api routes at all. Type: bool.
        'enabled' => env('DOCTOR_API_ENABLED', true),

        // URI prefix the API is served from. Type: string.
        'path' => env('DOCTOR_API_PATH', '_doctor/api'),

        // Middleware applied to the API routes (the viewDoctor gate runs on top). Type: array<int, string>.
        'middleware' => ['api'],

        // Gate checked outside local environments. Undefined gates deny access. Type: string.
        'gate' => 'viewDoctor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Saved Reports
    |--------------------------------------------------------------------------
    |
    | Completed scans are stateless by default. Runtime scan options may save
    | or skip one scan without mutating this configuration.
    |
    */
    'reports' => [
        'enabled' => true,
        // Single project-relative base directory; saved reports and scan status
        // live in its 'reports' and 'scans' subdirectories. Type: string.
        'path' => 'doctor',
        // Allow destructive dashboard/API report deletion endpoints. Type: bool.
        'allow_http_deletes' => env('DOCTOR_REPORTS_ALLOW_HTTP_DELETES', true),
        // Filesystem disk configuration name used to read/write saved reports and scan status,
        // as configured in config/filesystems.php. Type: string.
        'disk' => env('DOCTOR_REPORTS_DISK', 'local'),
        // the newest report is never deleted; within "keep_all" every report survives;
        // beyond that, only the newest report per day/week/month/year survives its
        // tier; anything older than the yearly tier is removed outright.
        'retention' => [
            // Keep every report for this many days. Type: int.
            'keep_all_for_days' => 7,
            // After that, keep the newest report per day for this many days. Type: int.
            'keep_daily_for_days' => 16,
            // After that, keep the newest report per week for this many weeks. Type: int.
            'keep_weekly_for_weeks' => 8,
            // After that, keep the newest report per month for this many months. Type: int.
            'keep_monthly_for_months' => 4,
            // After that, keep the newest report per year for this many years. Type: int.
            'keep_yearly_for_years' => 2,
            // Once the tiers above are applied, also delete the oldest remaining
            // reports until total size is under this many megabytes. Type: ?int (null = unlimited).
            'delete_oldest_when_using_more_megabytes_than' => null,
            // How often doctor:prune-reports runs on the scheduler. Type: string,
            // one of: hourly, daily, weekly, monthly. Unknown values fall back to daily.
            'schedule' => env('DOCTOR_REPORTS_PRUNE_SCHEDULE', 'daily'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Queue
    |--------------------------------------------------------------------------
    |
    | If defined, Doctor scans will be dispatched to this queue connection.
    |
    */
    'queue' => [
        'connection' => env('DOCTOR_QUEUE_CONNECTION', null),
        'queue' => env('DOCTOR_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Integrations
    |--------------------------------------------------------------------------
    |
    | Compact output and optional local AI-agent integrations. These defaults
    | are read-only and do not alter existing human-facing output surfaces.
    |
    */
    'ai' => [
        'mcp' => [
            // Gate the local `doctor:mcp` stdio server command. Type: bool.
            'enabled' => env('DOCTOR_MCP_ENABLED', false),

            // Allow-list of MCP tool names to expose. Empty = all built-in tools.
            // Otherwise list the names to keep, e.g. ['doctor_scan', 'doctor_list_rules'].
            // Available: doctor_scan, doctor_scan_files, doctor_scan_changed,
            // doctor_list_rules, doctor_explain_rule, doctor_resolve_plan.
            // Type: array<int, string>.
            'tools' => [
                'doctor_scan',
                'doctor_scan_files',
                'doctor_scan_changed',
                'doctor_list_rules',
                'doctor_explain_rule',
                'doctor_resolve_plan',
            ],
        ],

        'skill' => [
            // Map of AI client => project-relative path `doctor:install-skill` writes
            // the packaged skill to. Skill-format clients (claude/codex/opencode) read a
            // folder's SKILL.md; rules-format clients (kilocode/augment) auto-discover
            // markdown under a rules dir; context-file clients (gemini/qwen) need the file
            // imported from their GEMINI.md/QWEN.md (the command prints how). The `name:`
            // in the skill must match the leaf directory for SKILL.md clients.
            // Type: array<string, string>.
            'clients' => [
                'claude' => '.claude/skills/laravel-doctor/SKILL.md',
                'codex' => '.agents/skills/laravel-doctor/SKILL.md',
                'opencode' => '.opencode/skills/laravel-doctor/SKILL.md',
                'kilocode' => '.kilocode/rules/laravel-doctor.md',
                'augment' => '.augment/rules/laravel-doctor.md',
                'gemini' => '.gemini/laravel-doctor.md',
                'qwen' => '.qwen/laravel-doctor.md',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failure Thresholds
    |--------------------------------------------------------------------------
    |
    | Default command exit policy thresholds. CLI --fail-on-* options always
    | override these values.
    |
    */
    'fail_on' => [
        /*
         * Default severity threshold for a non-zero command exit. Accepted
         * values are null, 'error', 'warning', 'info', or 'critical'.
         */
        // 'severity' => 'error',
        'severity' => null,

        /*
         * Default confidence threshold for a non-zero command exit. Accepted
         * values are null, 'high', 'medium', or 'low'.
         */
        // 'confidence' => 'high',
        'confidence' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime
    |--------------------------------------------------------------------------
    |
    | Booted runtime rule defaults. Runtime checks boot Laravel and must remain
    | read-only; HTTP probe paths should point at safe GET endpoints.
    |
    */
    'runtime' => [
        /*
         * Enable booted runtime rules by default for doctor:scan. Type: bool.
         * The --no-booted CLI flag overrides this setting.
         */
        'enabled' => env('DOCTOR_RUNTIME_ENABLED', true),

        /*
         * Read-only HTTP paths used by runtime probe rules. Type: array<int,
         * string>. Values should be project-relative paths or routes.
         */
        // 'probe_paths' => ['/health', '/catalog'],
        'probe_paths' => [],

        /*
         * Minimum free disk space before health.disk-space-low reports a
         * finding. Type: int, in megabytes.
         */
        // 'disk_free_space_threshold_mb' => 2048,
        'disk_free_space_threshold_mb' => 1024,

        /*
         * Timeout for runtime socket and HTTP probes. Type: int, in seconds.
         */
        // 'timeout_seconds' => 2,
        'timeout_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependency Audit
    |--------------------------------------------------------------------------
    |
    | Composer dependency checks are strictly opt-in through --audit-dependencies.
    | The command is fixed by the package unless tests or hosts override it.
    |
    */
    'dependency_audit' => [
        /*
         * Enable Composer dependency audit checks by default for doctor:scan.
         * Type: bool. The --no-audit CLI flag overrides this setting.
         */
        'enabled' => env('DOCTOR_DEPENDENCY_AUDIT_ENABLED', true),

        // Composer binary/command used to run audit/outdated/validate. Type: string|array<int, string>.
        'composer_command' => 'composer',

        // Timeout for each Composer subprocess call. Type: int, in seconds.
        'timeout_seconds' => 30,
    ],
];
