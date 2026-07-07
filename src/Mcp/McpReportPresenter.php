<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Mcp;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Output\DoctorOutput;
use kayedspace\Doctor\Rules\RuleCatalog;

class McpReportPresenter
{
    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(DoctorReport $report): array
    {
        $output = new DoctorOutput($report->getFindings());
        $compact = $output->toCompactArray($this->catalog);

        $status = $report->getStatus();

        return [
            'status' => $status,
            'rules' => $compact['rules'],
            'findings' => $compact['findings'],
            'errors' => array_map(
                static fn ($error): array => $error->toArray(),
                $report->getErrors()
            ),
        ];
    }
}
