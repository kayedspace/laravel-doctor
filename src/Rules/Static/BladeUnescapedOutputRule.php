<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;

class BladeUnescapedOutputRule extends AbstractTextRule
{
    protected const RULE_ID = RuleId::SecurityBladeUnescapedOutput;

    protected const BLADE_ONLY = true;

    protected const PATTERNS = ['/{!!\\s*(?:request\\(|\\$request|\\$user|auth\\().*?!!}/is'];
}
