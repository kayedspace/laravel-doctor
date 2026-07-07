<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\SslVerificationDisabledVisitor;

class SslVerificationDisabledRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecuritySslVerificationDisabled;

    protected const VISITOR = SslVerificationDisabledVisitor::class;
}
