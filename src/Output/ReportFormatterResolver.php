<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Output;

use kayedspace\Doctor\Rules\RuleCatalog;

class ReportFormatterResolver
{
    public function __construct(
        private readonly RuleCatalog $catalog,
    ) {}

    public function resolve(string $format): ReportFormatter
    {
        return match ($format) {
            'console' => new ConsoleReportFormatter,
            'compact-json' => new CompactJsonReportFormatter($this->catalog),
            'json' => new JsonReportFormatter,
            'markdown' => new MarkdownReportFormatter,
            'sarif' => new SarifReportFormatter,
            default => throw new \InvalidArgumentException('Unsupported output format'),
        };
    }
}
