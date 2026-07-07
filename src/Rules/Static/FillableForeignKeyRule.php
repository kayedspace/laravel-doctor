<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\FillableForeignKeyVisitor;

class FillableForeignKeyRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityFillableForeignKey;

    protected const VISITOR = FillableForeignKeyVisitor::class;

    protected const PATH_GLOB = 'app/Models/*';
}
