<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\NodeVisitors\GlobalModelUnguardVisitor;

class GlobalModelUnguardRule extends AbstractStaticRule
{
    protected const RULE_ID = RuleId::SecurityGlobalModelUnguard;

    /**
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (! empty($file->parseError)) {
                continue;
            }

            $visitor = new GlobalModelUnguardVisitor;
            $this->traverse($file, $visitor);

            foreach ($visitor->unguardCalls as $index => $call) {
                $line = $call['node']->getStartLine();

                $findings[] = $this->makeFinding(
                    file: $file,
                    line: $line,
                    index: $index,
                    message: "{$call['class']}::unguard() was called in '{$file->path}' on line {$line}.",
                    evidence: $call['class'].'::unguard()'
                )->confidence($call['baseModel'] ? Confidence::High : Confidence::Medium);
            }
        }

        return $findings;
    }
}
