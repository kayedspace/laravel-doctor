<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\DoctorFinding;
use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Scan\SourceFile;

abstract class AbstractTextRule extends AbstractStaticRule
{
    protected const RULE_ID = null;

    /**
     * @var array<int, string>
     */
    protected const PATTERNS = [];

    protected const BLADE_ONLY = false;

    /**
     * @param  array<int, SourceFile>  $files
     * @return array<int, DoctorFinding>
     */
    public function analyze(array $files = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (! $this->shouldAnalyze($file)) {
                continue;
            }

            foreach ($this->patterns() as $pattern) {
                if (! preg_match_all($pattern, $file->contents, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches[0] as $index => $match) {
                    $line = substr_count(substr($file->contents, 0, $match[1]), "\n") + 1;
                    $findings[] = $this->makeFinding(
                        file: $file,
                        line: $line,
                        index: count($findings) + $index,
                        message: "{$this->title()} in '{$file->path}' on line {$line}.",
                        evidence: trim($match[0]),
                    )->confidence($this->confidence());
                }
            }
        }

        return $findings;
    }

    protected function confidence(): Confidence
    {
        return $this->id()->defaultConfidence();
    }

    /**
     * @return array<int, string>
     */
    protected function patterns(): array
    {
        return static::PATTERNS;
    }

    protected function shouldAnalyze(SourceFile $file): bool
    {
        if (static::BLADE_ONLY && ! $file->isBlade()) {
            return false;
        }

        return parent::shouldAnalyze($file);
    }

    protected function title(): string
    {
        return $this->id()->findingTitle();
    }
}
