<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\MissingDownMethodVisitor;

class MissingDownMethodRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::MigrationMissingDownMethod;

    protected const VISITOR = MissingDownMethodVisitor::class;

    protected const PATH_GLOB = 'database/migrations/*';
}
