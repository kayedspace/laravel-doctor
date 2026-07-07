<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Scan\SourceFile;

class InlineSuppression
{
    /**
     * @param  array<int, DoctorFinding>  $findings
     * @return array<int, DoctorFinding>
     */
    public function filter(SourceFile $file, array $findings): array
    {
        $comments = $this->commentsByLine($file->contents);

        return array_values(array_filter($findings, function (DoctorFinding $finding) use ($comments): bool {
            if ($finding->line === null) {
                return true;
            }

            foreach ([$finding->line, $finding->line - 1] as $line) {
                foreach ($comments[$line] ?? [] as $ruleId) {
                    if ($ruleId === null || $ruleId === $finding->ruleId) {
                        return false;
                    }
                }
            }

            return true;
        }));
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function commentsByLine(string $contents): array
    {
        $comments = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $index => $line) {
            if (! preg_match_all('/@doctor-ignore(?:\s+([a-z0-9_.-]+))?/i', $line, $matches)) {
                continue;
            }

            $lineNumber = $index + 1;
            foreach ($matches[1] as $ruleId) {
                $comments[$lineNumber][] = $ruleId === '' ? null : $ruleId;
            }
        }

        return $comments;
    }
}
