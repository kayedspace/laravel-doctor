<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Domain\DoctorFinding;

class AiMappedReport
{
    /**
     * @param  array<string, mixed>  $finding
     * @return array{rule: string, severity: string, location: string, message: string, remediation: string}
     */
    public static function rowFromArray(array $finding): array
    {
        $findingObj = DoctorFinding::fromArray($finding);
        $row = $findingObj->toCompactArray();
        $row['remediation'] = $findingObj->getFallbackRemediation();

        return $row;
    }
}
