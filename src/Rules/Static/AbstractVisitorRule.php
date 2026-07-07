<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Scan\SourceFile;
use kayedspace\Doctor\Support\NodeExpression;
use kayedspace\Doctor\Support\NodeVisitors\AbstractCollectingVisitor;
use PhpParser\Node;

abstract class AbstractVisitorRule extends AbstractStaticRule
{
    protected const RULE_ID = null;

    protected const VISITOR = AbstractCollectingVisitor::class;

    /**
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            if ($file->isBlade() || ! empty($file->parseError) || ! $this->shouldAnalyze($file)) {
                continue;
            }

            $visitor = $this->visitor();
            $visitor->addPatterns((array) Config::get("doctor.targets.{$this->id()->value}", []));
            $this->traverse($file, $visitor);

            foreach ($visitor->matches as $index => $node) {
                $findings[] = $this->makeFinding(
                    file: $file,
                    line: $node->getStartLine(),
                    index: $index,
                    message: $this->message($file, $node),
                    evidence: $this->evidence($node)
                )->confidence($this->confidence($node));
            }
        }

        return $findings;
    }

    protected function visitor(): AbstractCollectingVisitor
    {
        $class = static::VISITOR;

        return new $class;
    }

    protected function title(): string
    {
        return $this->id()->findingTitle();
    }

    protected function message(SourceFile $file, Node $node): string
    {
        return "{$this->title()} in '{$file->path}' on line {$node->getStartLine()}.";
    }

    protected function confidence(Node $node): Confidence
    {
        return $this->id()->defaultConfidence();
    }

    protected function evidence(Node $node): string
    {
        return NodeExpression::evidence($node);
    }
}
