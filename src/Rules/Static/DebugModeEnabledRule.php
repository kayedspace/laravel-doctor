<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;

class DebugModeEnabledRule extends AbstractTextRule
{
    protected const RULE_ID = RuleId::SecurityDebugModeEnabled;

    protected const PATH_GLOB = 'config/app.php';

    protected const PATTERNS = ["/['\"]debug['\"]\\s*=>\\s*true/i"];
}
