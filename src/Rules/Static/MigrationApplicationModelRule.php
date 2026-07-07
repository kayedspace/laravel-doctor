<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\NodeVisitors\MigrationApplicationModelVisitor;

class MigrationApplicationModelRule extends AbstractStaticRule
{
    protected const RULE_ID = RuleId::MigrationApplicationModel;

    /**
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (! str_starts_with($file->path, 'database/migrations/') || ! str_ends_with($file->path, '.php')) {
                continue;
            }

            if (! empty($file->parseError)) {
                continue;
            }

            $visitor = new MigrationApplicationModelVisitor;
            $this->traverse($file, $visitor);

            foreach ($visitor->references as $index => $reference) {
                $line = $reference['node']->getStartLine();

                $findings[] = $this->makeFinding(
                    file: $file,
                    line: $line,
                    index: $index,
                    message: "Migration '{$file->path}' references {$reference['class']} on line {$line}.",
                    evidence: $reference['class'],
                );
            }
        }

        return $findings;
    }
}
