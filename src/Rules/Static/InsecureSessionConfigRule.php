<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;

class InsecureSessionConfigRule extends AbstractTextRule
{
    protected const RULE_ID = RuleId::SecurityInsecureSessionConfig;

    protected const PATH_GLOB = 'config/session.php';

    protected const PATTERNS = [
        "/['\"]secure['\"]\\s*=>\\s*false/i",
        "/['\"]http_only['\"]\\s*=>\\s*false/i",
        "/['\"]same_site['\"]\\s*=>\\s*['\"](?:none|null)?['\"]/i",
    ];
}
