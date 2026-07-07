<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Contracts\DoctorRule;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Rules\Concerns\HasRuleMetadata;
use kayedspace\Doctor\Support\Fingerprints\FindingFingerprint;
use kayedspace\Doctor\Support\Wildcard;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

abstract class AbstractStaticRule implements DoctorRule
{
    use HasRuleMetadata;

    protected const RULE_ID = null;

    protected const PATH_GLOB = null;

    protected function makeFinding(SourceFile $file, int $line, int $index, string $message, string $evidence): DoctorFinding
    {
        return DoctorFinding::make($this->id()->value)
            ->id($this->findingId($file, $line, $index, $evidence))
            ->title($this->id()->findingTitle())
            ->message($message)
            ->severity($this->effectiveSeverity())
            ->confidence($this->id()->defaultConfidence())
            ->evidence($evidence)
            ->file($file->path)
            ->line($line)
            ->remediation($this->id()->remediation())
            ->tags($this->id()->tags());
    }

    protected function findingId(SourceFile $file, int $line, int $index, string $evidence = ''): string
    {
        return FindingFingerprint::make($this->id()->value, $file->path, $evidence, $line, $index);
    }

    protected function shouldAnalyze(SourceFile $file): bool
    {
        return static::PATH_GLOB === null || Wildcard::matchesAny($file->path, static::PATH_GLOB);
    }

    protected function traverse(SourceFile $file, NodeVisitor $visitor): NodeVisitor
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($file->syntaxTree);

        return $visitor;
    }
}
