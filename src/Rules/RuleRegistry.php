<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\DoctorRequest;
use kayedspace\Doctor\Domain\Enums\RuleCapability;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\RuleSelection;
use kayedspace\Doctor\Rules\Booted\AbstractBootedRule;
use kayedspace\Doctor\Rules\Booted\AppKeyMissingRule;
use kayedspace\Doctor\Rules\Booted\CacheFlushSharedStoreRule;
use kayedspace\Doctor\Rules\Booted\CacheUnreachableRule;
use kayedspace\Doctor\Rules\Booted\DatabaseUnreachableRule;
use kayedspace\Doctor\Rules\Booted\DiskSpaceLowRule;
use kayedspace\Doctor\Rules\Booted\LoginNotThrottledRule;
use kayedspace\Doctor\Rules\Booted\MaintenanceModeRule;
use kayedspace\Doctor\Rules\Booted\MissingSecurityHeadersRule;
use kayedspace\Doctor\Rules\Booted\PendingMigrationsRule;
use kayedspace\Doctor\Rules\Booted\QueueDispatchBeforeCommitRule;
use kayedspace\Doctor\Rules\Booted\QueueTimeoutRetryAfterRule;
use kayedspace\Doctor\Rules\Booted\QueueUniqueLockStoreRule;
use kayedspace\Doctor\Rules\Booted\RuntimeNPlusOneRule;
use kayedspace\Doctor\Rules\Booted\SchedulerSingleServerLockStoreRule;
use kayedspace\Doctor\Rules\Booted\StorageNotWritableRule;
use kayedspace\Doctor\Rules\Booted\UnsafeDriverRule;
use kayedspace\Doctor\Rules\Dependency\AbandonedPackageRule;
use kayedspace\Doctor\Rules\Dependency\AbstractDependencyRule;
use kayedspace\Doctor\Rules\Dependency\DevInProductionRule;
use kayedspace\Doctor\Rules\Dependency\KnownVulnerabilityRule;
use kayedspace\Doctor\Rules\Dependency\ManifestHealthRule;
use kayedspace\Doctor\Rules\Dependency\OutdatedPackageRule;
use kayedspace\Doctor\Rules\Static\AbstractStaticRule;
use kayedspace\Doctor\Rules\Static\AllThenFilterRule;
use kayedspace\Doctor\Rules\Static\BladeUnescapedOutputRule;
use kayedspace\Doctor\Rules\Static\CommandInjectionRule;
use kayedspace\Doctor\Rules\Static\ConfigCallInConfigFileRule;
use kayedspace\Doctor\Rules\Static\CountViaCollectionRule;
use kayedspace\Doctor\Rules\Static\CsrfExceptWildcardRule;
use kayedspace\Doctor\Rules\Static\DebugFunctionRule;
use kayedspace\Doctor\Rules\Static\DebugModeEnabledRule;
use kayedspace\Doctor\Rules\Static\DynamicEvalRule;
use kayedspace\Doctor\Rules\Static\DynamicViewPathRule;
use kayedspace\Doctor\Rules\Static\EnvCallOutsideConfigRule;
use kayedspace\Doctor\Rules\Static\FillableForeignKeyRule;
use kayedspace\Doctor\Rules\Static\GlobalModelUnguardRule;
use kayedspace\Doctor\Rules\Static\InsecureSessionConfigRule;
use kayedspace\Doctor\Rules\Static\MassAssignmentUnguardedRule;
use kayedspace\Doctor\Rules\Static\MigrationApplicationModelRule;
use kayedspace\Doctor\Rules\Static\MissingDownMethodRule;
use kayedspace\Doctor\Rules\Static\PathTraversalFileAccessRule;
use kayedspace\Doctor\Rules\Static\RawSqlInterpolationRule;
use kayedspace\Doctor\Rules\Static\SslVerificationDisabledRule;
use kayedspace\Doctor\Rules\Static\UnencryptedCookieRule;
use kayedspace\Doctor\Rules\Static\UnserializeUntrustedRule;
use kayedspace\Doctor\Rules\Static\UnvalidatedRedirectRule;
use kayedspace\Doctor\Rules\Static\VerboseDebugLoggingRule;
use kayedspace\Doctor\Rules\Static\WeakHashAlgorithmRule;
use kayedspace\Doctor\Rules\Static\WeakHashingRoundsRule;
use kayedspace\Doctor\Support\Wildcard;

class RuleRegistry
{
    /**
     * @var array<string, DoctorRule>
     */
    protected array $rules = [];

    /**
     * @param  array<int, DoctorRule>  $rules
     */
    public function __construct(array $rules = [])
    {
        foreach ($rules as $rule) {
            $this->rules[$rule->id()->value] = $rule;
        }
    }

    public static function default(): self
    {
        return new self([
            new DebugFunctionRule,
            new EnvCallOutsideConfigRule,
            new MigrationApplicationModelRule,
            new GlobalModelUnguardRule,
            new RawSqlInterpolationRule,
            new MassAssignmentUnguardedRule,
            new ConfigCallInConfigFileRule,
            new MissingDownMethodRule,
            new AllThenFilterRule,
            new CountViaCollectionRule,
            new CommandInjectionRule,
            new DynamicEvalRule,
            new UnserializeUntrustedRule,
            new SslVerificationDisabledRule,
            new CsrfExceptWildcardRule,
            new InsecureSessionConfigRule,
            new DebugModeEnabledRule,
            new WeakHashingRoundsRule,
            new VerboseDebugLoggingRule,
            new UnvalidatedRedirectRule,
            new DynamicViewPathRule,
            new PathTraversalFileAccessRule,
            new WeakHashAlgorithmRule,
            new BladeUnescapedOutputRule,
            new FillableForeignKeyRule,
            new UnencryptedCookieRule,

            // Booted rules
            new QueueTimeoutRetryAfterRule,
            new QueueDispatchBeforeCommitRule,
            new QueueUniqueLockStoreRule,
            new SchedulerSingleServerLockStoreRule,
            new CacheFlushSharedStoreRule,
            new DatabaseUnreachableRule,
            new CacheUnreachableRule,
            new DiskSpaceLowRule,
            new StorageNotWritableRule,
            new PendingMigrationsRule,
            new MaintenanceModeRule,
            new AppKeyMissingRule,
            new UnsafeDriverRule,
            new MissingSecurityHeadersRule,
            new LoginNotThrottledRule,
            new RuntimeNPlusOneRule,

            // Dependency rules
            new KnownVulnerabilityRule,
            new AbandonedPackageRule,
            new OutdatedPackageRule,
            new DevInProductionRule,
            new ManifestHealthRule,
        ]);
    }

    /**
     * @return array<int, DoctorRule>
     */
    public function getRules(): array
    {
        return array_values($this->rules);
    }

    public function select(DoctorRequest $request): RuleSelection
    {
        $this->validateConfiguredRuleIds();

        $requestedRules = $request->getRules();
        $requestedPacks = $request->getPacks();
        $rules = $this->configuredRules();
        $selectedRules = [];

        if (empty($requestedRules) && empty($requestedPacks)) {
            $configPacks = Config::get('doctor.packs');
            if (is_array($configPacks) && ! empty($configPacks)) {
                $validPacks = [];
                foreach ($this->rules as $rule) {
                    $validPacks[$rule->category()->value] = true;
                }
                foreach ($configPacks as $pack) {
                    if (! isset($validPacks[$pack])) {
                        throw new \InvalidArgumentException("Unknown rule pack in configuration: {$pack}");
                    }
                    foreach ($rules as $rule) {
                        if ($rule->category()->value === $pack) {
                            $selectedRules[$rule->id()->value] = $rule;
                        }
                    }
                }
            } else {
                $selectedRules = array_filter(
                    $rules,
                    fn (DoctorRule $rule): bool => $request->shouldAuditDependencies()
                        || ! in_array(RuleCapability::Dependency, $rule->capabilities(), true)
                );
            }
        } else {
            foreach ($requestedRules as $ruleId) {
                if (! isset($this->rules[$ruleId])) {
                    throw new \InvalidArgumentException("Unknown rule: {$ruleId}");
                }
                if (isset($rules[$ruleId])) {
                    $selectedRules[$ruleId] = $rules[$ruleId];
                }
            }

            $validPacks = [];
            foreach ($this->rules as $rule) {
                $validPacks[$rule->category()->value] = true;
            }

            foreach ($requestedPacks as $pack) {
                if (! isset($validPacks[$pack])) {
                    throw new \InvalidArgumentException("Unknown rule pack: {$pack}");
                }
                foreach ($rules as $rule) {
                    if ($rule->category()->value === $pack) {
                        $selectedRules[$rule->id()->value] = $rule;
                    }
                }
            }
        }

        $eligibleRules = [];
        $skippedRules = [];
        $allowedCapabilities = [RuleCapability::Static];

        if ($request->getBootPolicy() === 'booted') {
            $allowedCapabilities[] = RuleCapability::Booted;
        }
        if ($request->shouldAuditDependencies()) {
            $allowedCapabilities[] = RuleCapability::Dependency;
        }

        foreach ($selectedRules as $rule) {
            $canRun = true;
            $missingCap = '';

            foreach ($rule->capabilities() as $cap) {
                if (! in_array($cap, $allowedCapabilities, true)) {
                    $canRun = false;
                    $missingCap = $cap->value;
                    break;
                }
            }

            if ($canRun) {
                $eligibleRules[] = $rule;
            } else {
                $skippedRules[$rule->id()->value] = "Rule requires '{$missingCap}' capability which is not available in the current boot policy.";
            }
        }

        return new RuleSelection(
            requestedPacks: $requestedPacks,
            requestedRules: $requestedRules,
            defaultRules: empty($requestedRules) && empty($requestedPacks) ? array_keys($rules) : [],
            eligibleRules: $eligibleRules,
            skippedRules: $skippedRules
        );
    }

    /**
     * @return array<string, DoctorRule>
     */
    private function configuredRules(): array
    {
        $enabled = $this->flattenConfigMap(Config::get('doctor.rules', []));
        $severities = $this->flattenConfigMap(Config::get('doctor.severities', []));
        $rules = [];

        foreach ($this->rules as $id => $rule) {
            if ($this->resolveConfigValue($id, $enabled) === false) {
                continue;
            }

            $severity = $this->resolveConfigValue($id, $severities);
            if (($rule instanceof AbstractStaticRule || $rule instanceof AbstractBootedRule || $rule instanceof AbstractDependencyRule) && $severity !== null) {
                $rule->withSeverityOverride(Severity::from((string) $severity));
            }

            $rules[$id] = $rule;
        }

        return $rules;
    }

    private function validateConfiguredRuleIds(): void
    {
        foreach (['doctor.rules', 'doctor.severities'] as $key) {
            foreach (array_keys($this->flattenConfigMap(Config::get($key, []))) as $ruleId) {
                if ($this->isPattern($ruleId)) {
                    $matches = false;

                    foreach (array_keys($this->rules) as $knownRuleId) {
                        if (Wildcard::matchesAny($knownRuleId, $ruleId)) {
                            $matches = true;
                            break;
                        }
                    }

                    if (! $matches) {
                        throw new \InvalidArgumentException("No rules match pattern: {$ruleId}");
                    }

                    continue;
                }

                if (! isset($this->rules[$ruleId])) {
                    throw new \InvalidArgumentException("Unknown rule: {$ruleId}");
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function resolveConfigValue(string $id, array $map): mixed
    {
        if (array_key_exists($id, $map)) {
            return $map[$id];
        }

        $value = null;
        foreach ($map as $pattern => $configuredValue) {
            if ($this->isPattern((string) $pattern) && Wildcard::matchesAny($id, (string) $pattern)) {
                $value = $configuredValue;
            }
        }

        return $value;
    }

    private function isPattern(string $value): bool
    {
        return str_contains($value, '*')
            || str_contains($value, '?')
            || str_contains($value, '[');
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function flattenConfigMap(array $map, string $prefix = ''): array
    {
        $flat = [];

        foreach ($map as $key => $value) {
            $id = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $flat += $this->flattenConfigMap($value, $id);
            } else {
                $flat[$id] = $value;
            }
        }

        return $flat;
    }
}
