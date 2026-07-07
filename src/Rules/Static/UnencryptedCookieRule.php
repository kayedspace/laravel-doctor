<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Static;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\NodeVisitors\UnencryptedCookieVisitor;

class UnencryptedCookieRule extends AbstractVisitorRule
{
    protected const RULE_ID = RuleId::SecurityUnencryptedCookie;

    protected const VISITOR = UnencryptedCookieVisitor::class;

    protected const PATH_GLOB = '*EncryptCookies.php';
}
