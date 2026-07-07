<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\MassAssignmentUnguardedVisitor;

class MassAssignmentUnguardedRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityMassAssignmentUnguarded;

    protected const VISITOR = MassAssignmentUnguardedVisitor::class;

    protected const PATH_GLOB = 'app/Models/*';
}
