<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules;

use kayedspace\Doctor\Contracts\DoctorRule;

class RuleCatalog
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $cache = null;

    public function __construct(
        private readonly RuleRegistry $registry,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $rules = array_map(
            fn (DoctorRule $rule): array => $this->entry($rule),
            $this->registry->getRules()
        );

        usort(
            $rules,
            static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id'])
        );

        return $this->cache = $rules;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $ruleId): ?array
    {
        foreach ($this->all() as $rule) {
            if ($rule['id'] === $ruleId) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(DoctorRule $rule): array
    {
        return [
            'id' => $rule->id()->value,
            'name' => $rule->name(),
            'category' => $rule->category()->value,
            'severity' => $rule->defaultSeverity()->value,
            'confidence' => $rule->defaultConfidence()->value,
            'capabilities' => array_map(
                static fn ($capability): string => $capability->value,
                $rule->capabilities()
            ),
            'beta' => $rule->isBeta(),
            'description' => $rule->description(),
            'remediation' => $rule->remediation(),
            'examples' => $rule->examples(),
        ];
    }
}
