<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Rules\RuleCatalog;

class RemediationPlanner
{
    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    /**
     * @param  array<int, DoctorFinding>  $findings
     * @return array{groups: array<int, array<string, mixed>>}
     */
    public function plan(array $findings): array
    {
        $groups = [];

        foreach ($findings as $finding) {
            $rule = $finding->ruleId;
            $catalogEntry = $this->catalog->find($rule);
            $severity = $finding->severity->value;

            $groups[$rule] ??= [
                'rule' => $rule,
                'severity' => $severity,
                'severityWeight' => $finding->severity->weight(),
                'remediation' => (string) (($catalogEntry ? $catalogEntry['remediation'] : null) ?? $finding->remediation),
                'locations' => [],
            ];

            if ($finding->severity->weight() > $groups[$rule]['severityWeight']) {
                $groups[$rule]['severity'] = $severity;
                $groups[$rule]['severityWeight'] = $finding->severity->weight();
            }

            $groups[$rule]['locations'][$this->location($finding)] = true;
        }

        usort(
            $groups,
            static fn (array $a, array $b): int => ($b['severityWeight'] <=> $a['severityWeight'])
                ?: strcmp((string) $a['rule'], (string) $b['rule'])
        );

        return [
            'groups' => array_map(
                static function (array $group): array {
                    unset($group['severityWeight']);
                    $group['locations'] = array_keys($group['locations']);

                    return $group;
                },
                $groups
            ),
        ];
    }

    private function location(DoctorFinding $finding): string
    {
        if ($finding->file === null || $finding->file === '') {
            return 'project';
        }

        if ($finding->line === null) {
            return $finding->file;
        }

        return $finding->file.':'.$finding->line;
    }
}
