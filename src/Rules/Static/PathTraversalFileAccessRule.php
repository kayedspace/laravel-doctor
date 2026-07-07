<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\AbstractCollectingVisitor;
use kayedspace\Doctor\Support\NodeVisitors\RequestFlowSecurityVisitor;

class PathTraversalFileAccessRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityPathTraversalFileAccess;

    protected function visitor(): AbstractCollectingVisitor
    {
        return new RequestFlowSecurityVisitor('file');
    }
}
