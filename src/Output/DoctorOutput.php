<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\Support\Runtime\SecretRedactor;

class DoctorOutput
{
    /**
     * @var array<int, DoctorFinding>
     */
    private array $findings = [];

    /**
     * Create a new DoctorOutput instance.
     *
     * @param  array<int, DoctorFinding|array<string, mixed>>  $findings
     */
    public function __construct(array $findings)
    {
        foreach ($findings as $finding) {
            if ($finding instanceof DoctorFinding) {
                $this->findings[] = $finding;
            } elseif (is_array($finding)) {
                $this->findings[] = DoctorFinding::fromArray($finding);
            }
        }
    }

    /**
     * Get the findings collection.
     *
     * @return array<int, DoctorFinding>
     */
    public function getFindings(): array
    {
        return $this->findings;
    }

    /**
     * Transform the findings into a grouped compact array payload (rules catalog map + findings).
     *
     * @return array{rules: array<string, array<string, mixed>>, findings: array<int, array<string, mixed>>}
     */
    public function toCompactArray(RuleCatalog $catalog): array
    {
        $rulesMap = [];
        $compactFindings = [];

        foreach ($this->findings as $finding) {
            $ruleId = $finding->ruleId;

            if ($ruleId !== '' && ! isset($rulesMap[$ruleId])) {
                $catalogEntry = $catalog->find($ruleId);

                $title = $finding->title !== '' ? $finding->title : ($catalogEntry ? ($catalogEntry['name'] ?? '') : '');
                $remediation = $catalogEntry ? ($catalogEntry['remediation'] ?? $finding->getFallbackRemediation()) : $finding->getFallbackRemediation();
                $severity = $finding->severity->value;

                $rulesMap[$ruleId] = [
                    'title' => $title,
                    'severity' => $severity,
                    'remediation' => SecretRedactor::redact((string) $remediation),
                ];
            }

            $compactFindings[] = $finding->toCompactArray();
        }

        return [
            'rules' => $rulesMap,
            'findings' => $compactFindings,
        ];
    }

    /**
     * Transform the findings into a single paste-ready markdown string.
     */
    public function toMarkdown(): string
    {
        return implode("\n\n", array_map(
            static fn (DoctorFinding $finding): string => $finding->toMarkdown(),
            $this->findings
        ));
    }
}
