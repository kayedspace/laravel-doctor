<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\UnserializeUntrustedVisitor;

class UnserializeUntrustedRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityUnserializeUntrusted;

    protected const VISITOR = UnserializeUntrustedVisitor::class;
}
