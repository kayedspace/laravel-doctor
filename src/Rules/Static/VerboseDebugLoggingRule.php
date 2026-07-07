<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;

class VerboseDebugLoggingRule extends AbstractTextRule
{
    protected const RULE_ID = RuleId::SecurityVerboseDebugLogging;

    protected const PATH_GLOB = 'config/logging.php';

    protected const PATTERNS = ["/['\"]level['\"]\\s*=>\\s*['\"]debug['\"]/i"];
}
