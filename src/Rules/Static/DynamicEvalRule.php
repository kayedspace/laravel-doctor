<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\DynamicEvalVisitor;

class DynamicEvalRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityDynamicEval;

    protected const VISITOR = DynamicEvalVisitor::class;
}
