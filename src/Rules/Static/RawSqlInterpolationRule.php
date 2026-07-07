<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\RawSqlInterpolationVisitor;

class RawSqlInterpolationRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityRawSqlInterpolation;

    protected const VISITOR = RawSqlInterpolationVisitor::class;
}
