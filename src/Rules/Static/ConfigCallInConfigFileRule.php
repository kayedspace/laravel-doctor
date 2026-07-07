<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\ConfigCallInConfigFileVisitor;

class ConfigCallInConfigFileRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::FrameworkConfigCallInConfigFile;

    protected const VISITOR = ConfigCallInConfigFileVisitor::class;

    protected const PATH_GLOB = 'config/*';
}
