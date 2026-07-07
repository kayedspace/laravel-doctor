<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

enum RuleId: string
{
    case DevelopmentDebugFunction = 'development.debug-function';
    case EloquentAllThenFilter = 'eloquent.all-then-filter';
    case EloquentCountViaCollection = 'eloquent.count-via-collection';
    case FrameworkConfigCallInConfigFile = 'framework.config-call-in-config-file';
    case FrameworkEnvOutsideConfig = 'framework.env-outside-config';
    case MigrationApplicationModel = 'migration.application-model';
    case MigrationMissingDownMethod = 'migration.missing-down-method';
    case SecurityBladeUnescapedOutput = 'security.blade-unescaped-output';
    case SecurityCommandInjection = 'security.command-injection';
    case SecurityCsrfExceptWildcard = 'security.csrf-except-wildcard';
    case SecurityDebugModeEnabled = 'security.debug-mode-enabled';
    case SecurityDynamicEval = 'security.dynamic-eval';
    case SecurityDynamicViewPath = 'security.dynamic-view-path';
    case SecurityFillableForeignKey = 'security.fillable-foreign-key';
    case SecurityGlobalModelUnguard = 'security.global-model-unguard';
    case SecurityInsecureSessionConfig = 'security.insecure-session-config';
    case SecurityMassAssignmentUnguarded = 'security.mass-assignment-unguarded';
    case SecurityPathTraversalFileAccess = 'security.path-traversal-file-access';
    case SecurityRawSqlInterpolation = 'security.raw-sql-interpolation';
    case SecuritySslVerificationDisabled = 'security.ssl-verification-disabled';
    case SecurityUnencryptedCookie = 'security.unencrypted-cookie';
    case SecurityUnserializeUntrusted = 'security.unserialize-untrusted';
    case SecurityUnvalidatedRedirect = 'security.unvalidated-redirect';
    case SecurityVerboseDebugLogging = 'security.verbose-debug-logging';
    case SecurityWeakHashAlgorithm = 'security.weak-hash-algorithm';
    case SecurityWeakHashingRounds = 'security.weak-hashing-rounds';
    case DependencyKnownVulnerability = 'dependency.known-vulnerability';
    case DependencyAbandonedPackage = 'dependency.abandoned-package';
    case DependencyOutdated = 'dependency.outdated';
    case DependencyDevInProduction = 'dependency.dev-in-production';
    case DependencyManifestHealth = 'dependency.manifest-health';

    // Runtime Rules
    case QueueTimeoutRetryAfter = 'queue.timeout-retry-after';
    case QueueDispatchBeforeCommit = 'queue.dispatch-before-commit';
    case QueueUniqueLockStore = 'queue.unique-lock-store';
    case SchedulerSingleServerLockStore = 'scheduler.single-server-lock-store';
    case CacheFlushSharedStore = 'cache.flush-shared-store';
    case HealthDatabaseUnreachable = 'health.database-unreachable';
    case HealthCacheUnreachable = 'health.cache-unreachable';
    case HealthDiskSpaceLow = 'health.disk-space-low';
    case HealthStorageNotWritable = 'health.storage-not-writable';
    case HealthPendingMigrations = 'health.pending-migrations';
    case HealthMaintenanceMode = 'health.maintenance-mode';
    case ConfigAppKeyMissing = 'config.app-key-missing';
    case ConfigUnsafeDriver = 'config.unsafe-driver';
    case SecurityMissingSecurityHeaders = 'security.missing-security-headers';
    case SecurityLoginNotThrottled = 'security.login-not-throttled';
    case PerformanceRuntimeNPlusOne = 'performance.runtime-n-plus-one';

    public function ruleName(): string
    {
        return match ($this) {
            self::DevelopmentDebugFunction => 'Detect debug output functions',
            self::EloquentAllThenFilter => 'Detect all() followed by collection filtering',
            self::EloquentCountViaCollection => 'Detect collection count after get()',
            self::FrameworkConfigCallInConfigFile => 'Detect runtime calls inside config files',
            self::FrameworkEnvOutsideConfig => 'Detect env() outside configuration files',
            self::MigrationApplicationModel => 'Detect application models in migrations',
            self::MigrationMissingDownMethod => 'Detect migrations missing rollback logic',
            self::SecurityBladeUnescapedOutput => 'Detect unescaped Blade output',
            self::SecurityCommandInjection => 'Detect command injection risks',
            self::SecurityCsrfExceptWildcard => 'Detect wildcard CSRF exceptions',
            self::SecurityDebugModeEnabled => 'Detect hardcoded debug mode',
            self::SecurityDynamicEval => 'Detect dynamic evaluation risks',
            self::SecurityDynamicViewPath => 'Detect dynamic view path risks',
            self::SecurityFillableForeignKey => 'Detect fillable foreign keys',
            self::SecurityGlobalModelUnguard => 'Detect global model unguard calls',
            self::SecurityInsecureSessionConfig => 'Detect insecure session config',
            self::SecurityMassAssignmentUnguarded => 'Detect unguarded mass assignment',
            self::SecurityPathTraversalFileAccess => 'Detect path traversal file access risks',
            self::SecurityRawSqlInterpolation => 'Detect raw SQL interpolation',
            self::SecuritySslVerificationDisabled => 'Detect disabled SSL verification',
            self::SecurityUnencryptedCookie => 'Detect unencrypted cookie exceptions',
            self::SecurityUnserializeUntrusted => 'Detect unsafe deserialization',
            self::SecurityUnvalidatedRedirect => 'Detect unvalidated redirects',
            self::SecurityVerboseDebugLogging => 'Detect verbose debug logging',
            self::SecurityWeakHashAlgorithm => 'Detect weak hash algorithms',
            self::SecurityWeakHashingRounds => 'Detect weak hashing rounds',
            self::DependencyKnownVulnerability => 'Detect Composer packages with known vulnerabilities',
            self::DependencyAbandonedPackage => 'Detect abandoned Composer packages',
            self::DependencyOutdated => 'Detect outdated direct Composer requirements',
            self::DependencyDevInProduction => 'Detect dev packages used by application code',
            self::DependencyManifestHealth => 'Detect Composer manifest health issues',

            // Runtime Rules
            self::QueueTimeoutRetryAfter => 'Detect queue timeout and retry-after risks',
            self::QueueDispatchBeforeCommit => 'Detect queue dispatch before database commit',
            self::QueueUniqueLockStore => 'Detect unique lock store risk for queues',
            self::SchedulerSingleServerLockStore => 'Detect single-server scheduler lock store risk',
            self::CacheFlushSharedStore => 'Detect shared cache flush risks',
            self::HealthDatabaseUnreachable => 'Check database connection health',
            self::HealthCacheUnreachable => 'Check cache service health',
            self::HealthDiskSpaceLow => 'Check disk free space',
            self::HealthStorageNotWritable => 'Check storage directory writability',
            self::HealthPendingMigrations => 'Check for pending database migrations',
            self::HealthMaintenanceMode => 'Check if application is in maintenance mode',
            self::ConfigAppKeyMissing => 'Verify application key exists',
            self::ConfigUnsafeDriver => 'Detect unsafe drivers in production',
            self::SecurityMissingSecurityHeaders => 'Detect missing security headers',
            self::SecurityLoginNotThrottled => 'Verify login routes are throttled',
            self::PerformanceRuntimeNPlusOne => 'Detect duplicate database queries',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DevelopmentDebugFunction => 'Detect dd(), dump(), var_dump(), print_r(), ray(), and clock() calls in project code',
            self::EloquentAllThenFilter => 'Detect Model::all() chains filtered in memory',
            self::EloquentCountViaCollection => 'Detect get()->count() patterns that load rows before counting',
            self::FrameworkConfigCallInConfigFile => 'Detect config() and app() calls inside configuration files',
            self::FrameworkEnvOutsideConfig => 'Detect calls to env() outside of configuration files',
            self::MigrationApplicationModel => 'Detect migration files that reference App\\Models classes',
            self::MigrationMissingDownMethod => 'Detect migration classes with non-empty up() methods and no non-empty down() method',
            self::SecurityBladeUnescapedOutput => 'Detect unescaped Blade output from request or user input',
            self::SecurityCommandInjection => 'Detect command execution calls with non-literal input',
            self::SecurityCsrfExceptWildcard => 'Detect VerifyCsrfToken wildcard exceptions',
            self::SecurityDebugModeEnabled => 'Detect hardcoded debug mode enabled in configuration',
            self::SecurityDynamicEval => 'Detect eval(), assert(string), and create_function() with non-literal input',
            self::SecurityDynamicViewPath => 'Detect dynamic view paths from non-literal input',
            self::SecurityFillableForeignKey => 'Detect foreign key attributes in model fillable arrays',
            self::SecurityGlobalModelUnguard => 'Detect Model::unguard() and application model ::unguard() calls',
            self::SecurityInsecureSessionConfig => 'Detect hardcoded insecure session configuration',
            self::SecurityMassAssignmentUnguarded => 'Detect models with protected $guarded = []',
            self::SecurityPathTraversalFileAccess => 'Detect file access calls with request-derived input',
            self::SecurityRawSqlInterpolation => 'Detect raw SQL calls with concatenation or interpolation',
            self::SecuritySslVerificationDisabled => 'Detect Guzzle and cURL SSL verification disabled',
            self::SecurityUnencryptedCookie => 'Detect cookie names excluded from encryption',
            self::SecurityUnserializeUntrusted => 'Detect unserialize() calls on non-literal input',
            self::SecurityUnvalidatedRedirect => 'Detect redirects to request-derived destinations',
            self::SecurityVerboseDebugLogging => 'Detect hardcoded debug logging level',
            self::SecurityWeakHashAlgorithm => 'Detect md5() or sha1() over likely secrets or tokens',
            self::SecurityWeakHashingRounds => 'Detect bcrypt rounds below 10',
            self::DependencyKnownVulnerability => 'Report Composer packages with known security advisories from Composer audit output.',
            self::DependencyAbandonedPackage => 'Report installed Composer packages marked abandoned by Composer audit output.',
            self::DependencyOutdated => 'Report direct Composer requirements that have newer releases available.',
            self::DependencyDevInProduction => 'Report require-dev packages referenced from application source files.',
            self::DependencyManifestHealth => 'Report Composer manifest validation failures.',

            // Runtime Rules
            self::QueueTimeoutRetryAfter => 'Report queue timeout and retry-after timing issues where timeout exceeds retry-after.',
            self::QueueDispatchBeforeCommit => 'Identify queue jobs dispatched before database transactions commit.',
            self::QueueUniqueLockStore => 'Identify unique queue jobs using database/sync lock stores in production.',
            self::SchedulerSingleServerLockStore => 'Identify single-server scheduled tasks using database/sync lock stores in production.',
            self::CacheFlushSharedStore => 'Identify flush risks on shared cache stores without cache prefixes in production.',
            self::HealthDatabaseUnreachable => 'Verify that the database connection is reachable and responsive.',
            self::HealthCacheUnreachable => 'Verify that the default cache store is reachable and responsive.',
            self::HealthDiskSpaceLow => 'Verify that the primary storage disk has sufficient free space.',
            self::HealthStorageNotWritable => 'Verify that the storage directories are writable by the application.',
            self::HealthPendingMigrations => 'Verify that all database migrations have been executed.',
            self::HealthMaintenanceMode => 'Check if the application maintenance mode is currently active.',
            self::ConfigAppKeyMissing => 'Ensure the APP_KEY environment variable is set and valid in production.',
            self::ConfigUnsafeDriver => 'Identify sync/file drivers used for queues, cache, or session in production.',
            self::SecurityMissingSecurityHeaders => 'Identify missing security response headers from read-only HTTP probes.',
            self::SecurityLoginNotThrottled => 'Check if login routes apply rate-limiting middleware.',
            self::PerformanceRuntimeNPlusOne => 'Identify duplicate Eloquent query patterns during read-only HTTP probes.',
        };
    }

    public function category(): RuleCategory
    {
        return match ($this) {
            self::DevelopmentDebugFunction => RuleCategory::Development,
            self::EloquentAllThenFilter, self::EloquentCountViaCollection, self::PerformanceRuntimeNPlusOne => RuleCategory::Eloquent,
            self::FrameworkConfigCallInConfigFile,
            self::FrameworkEnvOutsideConfig,
            self::QueueTimeoutRetryAfter,
            self::QueueDispatchBeforeCommit,
            self::QueueUniqueLockStore,
            self::SchedulerSingleServerLockStore,
            self::CacheFlushSharedStore,
            self::ConfigAppKeyMissing,
            self::ConfigUnsafeDriver => RuleCategory::Framework,
            self::MigrationApplicationModel, self::MigrationMissingDownMethod => RuleCategory::Migration,
            self::DependencyKnownVulnerability,
            self::DependencyAbandonedPackage,
            self::DependencyOutdated,
            self::DependencyDevInProduction,
            self::DependencyManifestHealth => RuleCategory::Dependency,
            self::HealthDatabaseUnreachable,
            self::HealthCacheUnreachable,
            self::HealthDiskSpaceLow,
            self::HealthStorageNotWritable,
            self::HealthPendingMigrations,
            self::HealthMaintenanceMode => RuleCategory::Health,
            default => RuleCategory::Security,
        };
    }

    public function defaultSeverity(): Severity
    {
        return match ($this) {
            self::SecurityCommandInjection,
            self::SecurityDynamicEval,
            self::SecurityRawSqlInterpolation => Severity::Critical,
            self::DependencyKnownVulnerability => Severity::Error,
            self::FrameworkEnvOutsideConfig,
            self::SecurityGlobalModelUnguard,
            self::SecurityMassAssignmentUnguarded,
            self::SecuritySslVerificationDisabled,
            self::SecurityUnserializeUntrusted,
            self::HealthDatabaseUnreachable,
            self::HealthCacheUnreachable,
            self::HealthStorageNotWritable,
            self::ConfigAppKeyMissing,
            self::ConfigUnsafeDriver => Severity::Error,
            self::SecurityVerboseDebugLogging,
            self::SecurityWeakHashingRounds,
            self::DependencyOutdated,
            self::HealthMaintenanceMode => Severity::Info,
            default => Severity::Warning,
        };
    }

    public function findingTitle(): string
    {
        return match ($this) {
            self::DevelopmentDebugFunction => 'Debug function call found',
            self::EloquentAllThenFilter => 'Eloquent all() filtered in memory',
            self::EloquentCountViaCollection => 'Collection counted after get()',
            self::FrameworkConfigCallInConfigFile => 'Runtime helper call inside config file',
            self::FrameworkEnvOutsideConfig => 'env() call outside configuration file',
            self::MigrationApplicationModel => 'Application model referenced in migration',
            self::MigrationMissingDownMethod => 'Migration missing rollback method',
            self::SecurityBladeUnescapedOutput => 'Unescaped Blade output from user input',
            self::SecurityCommandInjection => 'Command execution receives non-literal input',
            self::SecurityCsrfExceptWildcard => 'CSRF wildcard exception found',
            self::SecurityDebugModeEnabled => 'Debug mode hardcoded on',
            self::SecurityDynamicEval => 'Dynamic evaluation found',
            self::SecurityDynamicViewPath => 'Dynamic view path found',
            self::SecurityFillableForeignKey => 'Foreign key is mass assignable',
            self::SecurityGlobalModelUnguard => 'Global model unguard call found',
            self::SecurityInsecureSessionConfig => 'Insecure session configuration',
            self::SecurityMassAssignmentUnguarded => 'Model allows all attributes for mass assignment',
            self::SecurityPathTraversalFileAccess => 'File access uses request input',
            self::SecurityRawSqlInterpolation => 'Raw SQL interpolation found',
            self::SecuritySslVerificationDisabled => 'SSL verification disabled',
            self::SecurityUnencryptedCookie => 'Cookie excluded from encryption',
            self::SecurityUnserializeUntrusted => 'Unsafe deserialization found',
            self::SecurityUnvalidatedRedirect => 'Redirect uses request input',
            self::SecurityVerboseDebugLogging => 'Verbose debug logging configured',
            self::SecurityWeakHashAlgorithm => 'Weak hash algorithm used for sensitive value',
            self::SecurityWeakHashingRounds => 'Weak hashing rounds configured',
            self::DependencyKnownVulnerability => 'Composer package has a known vulnerability',
            self::DependencyAbandonedPackage => 'Composer package is abandoned',
            self::DependencyOutdated => 'Composer package is outdated',
            self::DependencyDevInProduction => 'Dev dependency is used by application code',
            self::DependencyManifestHealth => 'Composer manifest validation failed',

            // Runtime Rules
            self::QueueTimeoutRetryAfter => 'Queue timeout exceeds retry-after',
            self::QueueDispatchBeforeCommit => 'Queue job dispatched before transaction commit',
            self::QueueUniqueLockStore => 'Risky unique lock store for queues',
            self::SchedulerSingleServerLockStore => 'Risky scheduler lock store',
            self::CacheFlushSharedStore => 'Cache flush shared store risk',
            self::HealthDatabaseUnreachable => 'Database connection unreachable',
            self::HealthCacheUnreachable => 'Cache connection unreachable',
            self::HealthDiskSpaceLow => 'Disk space is low',
            self::HealthStorageNotWritable => 'Storage directory not writable',
            self::HealthPendingMigrations => 'Pending database migrations',
            self::HealthMaintenanceMode => 'Application in maintenance mode',
            self::ConfigAppKeyMissing => 'Missing or invalid application key',
            self::ConfigUnsafeDriver => 'Unsafe driver configured in production',
            self::SecurityMissingSecurityHeaders => 'Missing security response headers',
            self::SecurityLoginNotThrottled => 'Login route is not throttled',
            self::PerformanceRuntimeNPlusOne => 'Duplicate database queries detected',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::DevelopmentDebugFunction => 'Remove the debug call or replace it with structured logging before shipping.',
            self::EloquentAllThenFilter => 'Move filtering into the query before loading records.',
            self::EloquentCountViaCollection => 'Use count() on the query before loading the collection.',
            self::FrameworkConfigCallInConfigFile => 'Avoid config() and app() inside config files; use literals or env() in config only.',
            self::FrameworkEnvOutsideConfig => "Move this environment variable read to a configuration file (e.g., config/app.php) and use config('key') here instead.",
            self::MigrationApplicationModel => 'Use schema operations or direct table queries instead of application models in migrations.',
            self::MigrationMissingDownMethod => 'Add a non-empty down() method that reverses the migration changes.',
            self::SecurityBladeUnescapedOutput => 'Use escaped Blade output unless the value is explicitly sanitized HTML.',
            self::SecurityCommandInjection => 'Avoid shell execution or pass validated arguments through a safe process API.',
            self::SecurityCsrfExceptWildcard => 'Replace wildcard CSRF exclusions with specific trusted callback paths.',
            self::SecurityDebugModeEnabled => 'Drive debug mode from environment configuration and disable it in production.',
            self::SecurityDynamicEval => 'Remove dynamic evaluation and dispatch through explicit code paths.',
            self::SecurityDynamicViewPath => 'Map user choices to known view names instead of passing dynamic values to view().',
            self::SecurityFillableForeignKey => 'Remove foreign keys from fillable or authorize relationship changes explicitly.',
            self::SecurityGlobalModelUnguard => 'Use Model::unguarded(fn () => ...) to scope mass-assignment changes to a callback.',
            self::SecurityInsecureSessionConfig => 'Use secure, HTTP-only cookies and a strict or lax same-site policy for sessions.',
            self::SecurityMassAssignmentUnguarded => 'Use a guarded list or explicit fillable attributes instead of protected $guarded = [].',
            self::SecurityPathTraversalFileAccess => 'Resolve files from trusted roots and validate requested names against an allow-list.',
            self::SecurityRawSqlInterpolation => 'Use parameter binding or query builder methods instead of interpolated raw SQL.',
            self::SecuritySslVerificationDisabled => 'Keep TLS certificate verification enabled for outbound requests.',
            self::SecurityUnencryptedCookie => 'Encrypt application cookies unless the value is intentionally public and integrity-protected.',
            self::SecurityUnserializeUntrusted => 'Use JSON or unserialize with strict allowed_classes for trusted payloads only.',
            self::SecurityUnvalidatedRedirect => 'Validate redirect destinations against an allow-list.',
            self::SecurityVerboseDebugLogging => 'Use a production-appropriate log level such as warning or error.',
            self::SecurityWeakHashAlgorithm => 'Use password_hash(), hash_hmac(), or a modern keyed hash appropriate to the value.',
            self::SecurityWeakHashingRounds => 'Use bcrypt rounds of at least 10 unless benchmarked otherwise.',
            self::DependencyKnownVulnerability => 'Upgrade the affected package to a non-vulnerable version or apply the vendor-recommended mitigation.',
            self::DependencyAbandonedPackage => 'Replace the abandoned package with the suggested alternative or remove it.',
            self::DependencyOutdated => 'Review the direct dependency and upgrade when compatible with the application.',
            self::DependencyDevInProduction => 'Move the package to require or remove application code that depends on require-dev packages.',
            self::DependencyManifestHealth => 'Fix composer.json validation errors and regenerate composer.lock if needed.',

            // Runtime Rules
            self::QueueTimeoutRetryAfter => 'Ensure the queue worker timeout is less than the connection retry_after (typically 5-10 seconds less) to prevent duplicate processing.',
            self::QueueDispatchBeforeCommit => 'Set dispatch_after_commit to true in queue config or call afterCommit() on jobs dispatched within transactions.',
            self::QueueUniqueLockStore => 'Configure a shared central lock store like redis or memcached for unique job locks in production.',
            self::SchedulerSingleServerLockStore => 'Configure a central cache driver like redis or memcached as the lock store for scheduled tasks.',
            self::CacheFlushSharedStore => 'Configure a unique cache prefix or use separate cache databases to isolate cache flushes.',
            self::HealthDatabaseUnreachable => 'Check your database credentials, host reachability, and server status.',
            self::HealthCacheUnreachable => 'Check your cache host reachability, credentials, and cache service status.',
            self::HealthDiskSpaceLow => 'Free up disk space or expand the storage volume.',
            self::HealthStorageNotWritable => 'Ensure the storage directory permissions and owner are set correctly for the web server/CLI process.',
            self::HealthPendingMigrations => 'Run php artisan migrate to apply pending schema updates.',
            self::HealthMaintenanceMode => 'Run php artisan up to disable maintenance mode when ready.',
            self::ConfigAppKeyMissing => 'Run php artisan key:generate to set a secure application key.',
            self::ConfigUnsafeDriver => 'Configure a production-ready driver (like database, redis, or memcached) for queues, cache, and session.',
            self::SecurityMissingSecurityHeaders => 'Add HSTS, X-Frame-Options, and X-Content-Type-Options headers to web responses.',
            self::SecurityLoginNotThrottled => 'Apply throttle middleware to authentication/login routes.',
            self::PerformanceRuntimeNPlusOne => 'Eager load relationships or cache query results to eliminate N+1 queries.',
        };
    }

    /**
     * @return array<int, string>
     */
    public function examples(): array
    {
        return match ($this) {
            self::SecurityRawSqlInterpolation => [
                'DB::raw("status = {$request->status}")',
                'DB::select("select * from users where email = {$email}")',
            ],
            self::SecurityCommandInjection => [
                'exec("php artisan ".$request->input("command"))',
            ],
            self::FrameworkEnvOutsideConfig => [],
            default => [],
        };
    }

    public function defaultConfidence(): Confidence
    {
        return match ($this) {
            self::SecurityVerboseDebugLogging,
            self::SecurityWeakHashAlgorithm,
            self::SecurityWeakHashingRounds,
            self::DependencyOutdated => Confidence::Low,
            self::DependencyAbandonedPackage,
            self::DependencyDevInProduction => Confidence::Medium,
            self::EloquentAllThenFilter,
            self::EloquentCountViaCollection,
            self::MigrationApplicationModel,
            self::SecurityBladeUnescapedOutput,
            self::SecurityCommandInjection,
            self::SecurityDebugModeEnabled,
            self::SecurityDynamicViewPath,
            self::SecurityFillableForeignKey,
            self::SecurityPathTraversalFileAccess,
            self::SecurityRawSqlInterpolation,
            self::SecurityUnencryptedCookie,
            self::SecurityUnserializeUntrusted,
            self::SecurityUnvalidatedRedirect,
            self::DependencyKnownVulnerability,
            self::DependencyManifestHealth,
            self::QueueDispatchBeforeCommit,
            self::QueueUniqueLockStore,
            self::SchedulerSingleServerLockStore,
            self::CacheFlushSharedStore,
            self::SecurityLoginNotThrottled,
            self::PerformanceRuntimeNPlusOne => Confidence::Medium,
            default => Confidence::High,
        };
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return match ($this) {
            self::DevelopmentDebugFunction => ['development', 'debug'],
            self::EloquentAllThenFilter,
            self::EloquentCountViaCollection => ['eloquent', 'performance'],
            self::FrameworkEnvOutsideConfig => ['security', 'best-practice'],
            self::MigrationApplicationModel => ['migration', 'model'],
            self::SecurityCommandInjection => ['security', 'command-injection'],
            self::SecurityCsrfExceptWildcard => ['security', 'csrf'],
            self::SecurityDynamicEval => ['security', 'eval'],
            self::SecurityGlobalModelUnguard,
            self::SecurityMassAssignmentUnguarded => ['security', 'mass-assignment'],
            self::SecurityRawSqlInterpolation => ['security', 'sql'],
            self::SecuritySslVerificationDisabled => ['security', 'transport'],
            self::SecurityUnserializeUntrusted => ['security', 'deserialization'],
            self::DependencyKnownVulnerability => ['dependency', 'security'],
            self::DependencyAbandonedPackage => ['dependency', 'abandoned'],
            self::DependencyOutdated => ['dependency', 'outdated', 'beta'],
            self::DependencyDevInProduction => ['dependency', 'dev', 'beta'],
            self::DependencyManifestHealth => ['dependency', 'manifest'],

            // Runtime Rules
            self::QueueTimeoutRetryAfter => ['framework', 'queue'],
            self::QueueDispatchBeforeCommit => ['framework', 'queue', 'beta'],
            self::QueueUniqueLockStore => ['framework', 'queue', 'beta'],
            self::SchedulerSingleServerLockStore => ['framework', 'scheduler', 'beta'],
            self::CacheFlushSharedStore => ['framework', 'cache', 'beta'],
            self::HealthDatabaseUnreachable => ['health', 'database'],
            self::HealthCacheUnreachable => ['health', 'cache'],
            self::HealthDiskSpaceLow => ['health', 'storage'],
            self::HealthStorageNotWritable => ['health', 'storage'],
            self::HealthPendingMigrations => ['health', 'database'],
            self::HealthMaintenanceMode => ['health'],
            self::ConfigAppKeyMissing => ['framework', 'config'],
            self::ConfigUnsafeDriver => ['framework', 'config'],
            self::SecurityMissingSecurityHeaders => ['security', 'headers'],
            self::SecurityLoginNotThrottled => ['security', 'throttle', 'beta'],
            self::PerformanceRuntimeNPlusOne => ['eloquent', 'performance', 'beta'],

            default => [$this->category()->value],
        };
    }

    public function isBeta(): bool
    {
        return match ($this) {
            self::EloquentAllThenFilter,
            self::EloquentCountViaCollection,
            self::SecurityBladeUnescapedOutput,
            self::SecurityDynamicViewPath,
            self::SecurityPathTraversalFileAccess,
            self::SecurityUnvalidatedRedirect,
            self::SecurityWeakHashAlgorithm,
            self::DependencyOutdated,
            self::DependencyDevInProduction,
            self::QueueDispatchBeforeCommit,
            self::QueueUniqueLockStore,
            self::SchedulerSingleServerLockStore,
            self::CacheFlushSharedStore,
            self::SecurityLoginNotThrottled,
            self::PerformanceRuntimeNPlusOne => true,
            default => false,
        };
    }

    /**
     * @return array<int, RuleCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::QueueTimeoutRetryAfter,
            self::QueueDispatchBeforeCommit,
            self::QueueUniqueLockStore,
            self::SchedulerSingleServerLockStore,
            self::CacheFlushSharedStore,
            self::HealthDatabaseUnreachable,
            self::HealthCacheUnreachable,
            self::HealthDiskSpaceLow,
            self::HealthStorageNotWritable,
            self::HealthPendingMigrations,
            self::HealthMaintenanceMode,
            self::ConfigAppKeyMissing,
            self::ConfigUnsafeDriver,
            self::SecurityMissingSecurityHeaders,
            self::SecurityLoginNotThrottled,
            self::PerformanceRuntimeNPlusOne => [RuleCapability::Booted],
            self::DependencyKnownVulnerability,
            self::DependencyAbandonedPackage,
            self::DependencyOutdated,
            self::DependencyDevInProduction,
            self::DependencyManifestHealth => [RuleCapability::Dependency],
            default => [RuleCapability::Static],
        };
    }
}
