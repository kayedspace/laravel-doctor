<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\AbstractCollectingVisitor;
use kayedspace\Doctor\Support\NodeVisitors\RequestFlowSecurityVisitor;

class UnvalidatedRedirectRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityUnvalidatedRedirect;

    protected function visitor(): AbstractCollectingVisitor
    {
        return new RequestFlowSecurityVisitor('redirect');
    }
}
