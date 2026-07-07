<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\NodeVisitors\DebugFunctionVisitor;
use PhpParser\Node;

class DebugFunctionRule extends AbstractStaticRule
{
    protected const RULE_ID = RuleId::DevelopmentDebugFunction;

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

            $visitor = new DebugFunctionVisitor;
            $this->traverse($file, $visitor);

            foreach ($visitor->calls as $index => $node) {
                $function = $node->name instanceof Node\Name ? $node->name->toString() : 'debug';
                $line = $node->getStartLine();

                $findings[] = $this->makeFinding(
                    file: $file,
                    line: $line,
                    index: $index,
                    message: "The {$function}() debug function was called in '{$file->path}' on line {$line}.",
                    evidence: $function.'(...)',
                );
            }
        }

        return $findings;
    }
}
