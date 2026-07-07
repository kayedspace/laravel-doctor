<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\CommandInjectionVisitor;

class CommandInjectionRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityCommandInjection;

    protected const VISITOR = CommandInjectionVisitor::class;
}
