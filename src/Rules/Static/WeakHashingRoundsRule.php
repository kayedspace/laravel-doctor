<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;

class WeakHashingRoundsRule extends AbstractTextRule
{
    protected const RULE_ID = RuleId::SecurityWeakHashingRounds;

    protected const PATH_GLOB = 'config/hashing.php';

    protected const PATTERNS = ["/['\"]rounds['\"]\\s*=>\\s*[0-9]/"];
}
