<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\NodeVisitors\EnvCallVisitor;
use PhpParser\Node;

class EnvCallOutsideConfigRule extends AbstractStaticRule
{
    protected const RULE_ID = RuleId::FrameworkEnvOutsideConfig;

    /**
     * Analyze the source files.
     *
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            // Negative case: config files are allowed to call env()
            if (str_starts_with($file->path, 'config/')) {
                continue;
            }

            if (! empty($file->parseError)) {
                continue;
            }

            $visitor = new EnvCallVisitor;
            $this->traverse($file, $visitor);

            foreach ($visitor->envCalls as $index => $node) {
                $line = $node->getStartLine();

                $isTestFile = str_starts_with($file->path, 'tests/');
                $severity = $isTestFile ? Severity::Warning : Severity::Error;
                $confidence = $isTestFile ? Confidence::Low : Confidence::High;

                $findings[] = $this->makeFinding(
                    file: $file,
                    line: $line,
                    index: $index,
                    message: "The env() helper was called in '{$file->path}' on line {$line}. Calls to env() outside config files return null when configuration is cached.",
                    evidence: 'env('.$this->getArgsText($node).')'
                )->confidence($confidence)->severity($severity);
            }
        }

        return $findings;
    }

    /**
     * Get a string representation of function call arguments.
     */
    private function getArgsText(Node\Expr\FuncCall $node): string
    {
        $args = [];
        foreach ($node->getArgs() as $arg) {
            if ($arg->value instanceof Node\Scalar\String_) {
                $args[] = "'".$arg->value->value."'";
            } else {
                $args[] = '...';
            }
        }

        return implode(', ', $args);
    }
}
