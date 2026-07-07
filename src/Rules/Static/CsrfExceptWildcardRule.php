<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\CsrfExceptWildcardVisitor;

class CsrfExceptWildcardRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityCsrfExceptWildcard;

    protected const VISITOR = CsrfExceptWildcardVisitor::class;

    protected const PATH_GLOB = '*VerifyCsrfToken.php';
}
